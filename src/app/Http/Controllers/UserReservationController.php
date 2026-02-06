<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\BusinessHour;
use App\Models\Customer;
use App\Models\ScheduledEmail;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;
use App\Mail\AdminReservationCanceledMail;
use App\Mail\UserReservationCanceledMail;
use App\Mail\ReservationConfirmedMail;
use App\Mail\AdminReservationNoticeMail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Database\QueryException;

class UserReservationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $reservations = Reservation::with('service')
            ->where('user_id', $user->id)
            ->orderByDesc('date')
            ->get()
            ->map(fn ($r) => [
                'id'           => $r->id,
                'service_name' => $r->service->name ?? '未設定',
                'date'         => Carbon::parse($r->date)->format('Y-m-d'),
                'time'         => Carbon::parse($r->start_time)->format('H:i'),
                'status'       => $r->status === 'canceled' ? 'キャンセル' : '確定',
            ]);

        return Inertia::render('Reservation/ReservationHistory', [
            'reservations' => $reservations,
        ]);
    }

    /**
     * -------------------------------------------------------------
     * ✅ マイページ：キャンセル確認（Blade confirm）
     * GET /mypage/reservations/{reservation}/cancel/confirm
     * -------------------------------------------------------------
     */
    public function cancelConfirm(Request $request, Reservation $reservation)
    {
        $user = $request->user();

        // ✅ 所有者チェック（他人の予約は見れない）
        if ((int) $reservation->user_id !== (int) $user->id) {
            abort(403);
        }

        $reservation->load('service');

        return view('reservations.cancel.confirm', [
            'title'       => 'キャンセル確認 | Lash Brow Ohana',
            'reservation' => $reservation,
            'isCanceled'  => ($reservation->status === 'canceled'),

            // ✅ マイページの cancel（POST）へ
            'action'      => route('mypage.reservations.cancel', ['id' => $reservation->id]),

            // ✅ マイページへ戻す
            'home'        => route('mypage.index'),
        ]);
    }

    public function cancel($id, Request $request)
    {
        $reservation = Reservation::where('user_id', $request->user()->id)
            ->with('service')
            ->findOrFail($id);

        // ✅ Blade（confirm）からのPOSTなら done.blade.php を返す
        // ✅ Inertia（XHR）なら従来通り redirect back + flash
        $isInertia = (bool) $request->header('X-Inertia');

        if ($reservation->status === 'canceled') {
            $message = 'すでにキャンセル済みです。';

            if ($isInertia) {
                return back()->with('message', $message);
            }

            return view('reservations.cancel.done', [
                'title'           => 'キャンセル結果 | Lash Brow Ohana',
                'reservation'     => $reservation,
                'alreadyCanceled' => true,
                'message'         => $message,
                'home'            => route('mypage.index'),
            ]);
        }

        // ✅ cancel_reason を保存（任意）
        $validated = $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $reservation->update([
            'status' => 'canceled',
            'cancel_reason' => $validated['cancel_reason'] ?? null,
        ]);

        try {
            $adminEmail = env('MAIL_ADMIN_ADDRESS', 'admin@lash-brow-ohana.local');
            Mail::to($adminEmail)->send(new AdminReservationCanceledMail($reservation));

            // ✅ ユーザー宛：reservation.email が空のケースに備えて、ログインユーザーemailへフォールバック
            $userEmail = $reservation->email ?: ($request->user()->email ?? null);

            if (!empty($userEmail)) {
                Mail::to($userEmail)->send(new UserReservationCanceledMail($reservation));
            } else {
                Log::warning('[キャンセル通知メール未送信: 宛先なし]', [
                    'reservation_id' => $reservation->id,
                    'user_id' => $request->user()->id ?? null,
                ]);
            }
        } catch (\Throwable $e) { // ✅ Exception → Throwable
            Log::error('[キャンセル通知メール送信エラー]', [
                'reservation_id' => $reservation->id,
                'email' => $reservation->email ?? '不明',
                'error' => $e->getMessage(),
            ]);
        }

        if ($isInertia) {
            return redirect()->back()->with('message', '予約をキャンセルしました。');
        }

        return view('reservations.cancel.done', [
            'title'           => 'キャンセル結果 | Lash Brow Ohana',
            'reservation'     => $reservation,
            'alreadyCanceled' => false,
            'message'         => '予約をキャンセルしました。',
            'home'            => route('mypage.index'),
        ]);
    }

    /**
     * -------------------------------------------------------------
     * ✅ マイページからの予約作成（user_id を必ず紐付け）
     * POST /mypage/reservations/store
     * -------------------------------------------------------------
     */
    public function storeFromMypage(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'service_id'  => ['required', 'exists:services,id'],
            'date'        => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time'  => ['required', 'date_format:H:i'],
            'phone'       => ['required', 'string', 'max:20'],     // ✅ マイページから予約でも必須にしてDB不整合を防止
            'notes'       => ['nullable', 'string', 'max:1000'],
        ]);

        $service  = Service::findOrFail($validated['service_id']);
        $duration = (int) ($service->duration_minutes ?? 30);

        $proposedStart = Carbon::parse($validated['date'] . ' ' . $validated['start_time']);
        $proposedEnd   = (clone $proposedStart)->addMinutes($duration);

        // ✅ 15分刻みチェック
        if (((int) $proposedStart->format('i')) % 15 !== 0) {
            return back()
                ->with('message', '開始時刻は15分刻みで選択してください。')
                ->withInput();
        }

        // ✅ 12時間ルール
        $minStart = now()->addHours(12);
        if ($proposedStart->lt($minStart)) {
            return back()
                ->with('message', 'ご予約は現在時刻から12時間以降の枠のみ受付可能です。')
                ->withInput();
        }

        // ✅ BusinessHour（営業時間）チェック（Api\ReservationController と同等）
        [$openTime, $closeTime, $bhMessage] = $this->resolveOpenCloseByBusinessHour(Carbon::parse($validated['date']));

        if (!$openTime || !$closeTime) {
            return back()
                ->with('message', $bhMessage ?: '本日は終日休業のため予約できません。')
                ->withInput();
        }

        if ($proposedStart->lt($openTime) || $proposedEnd->gt($closeTime)) {
            return back()
                ->with('message', '選択された時間は営業時間外です。')
                ->withInput();
        }

        // ✅ 重複予約チェック（confirmed のみ）
        $isOverlapping = Reservation::where('date', $validated['date'])
            ->where('status', 'confirmed')
            ->where(function ($query) use ($proposedStart, $proposedEnd) {
                $query->where('start_time', '<', $proposedEnd->format('H:i:s'))
                      ->where('end_time',   '>', $proposedStart->format('H:i:s'));
            })
            ->exists();

        if ($isOverlapping) {
            return back()
                ->with('message', '選択された時間枠は既に予約済みです。')
                ->withInput();
        }

        // ✅ ログインユーザー情報を必ず反映（= 予約番号紐付け不要）
        $baseName  = $user->name ?? 'ユーザー';
        $baseEmail = $user->email ?? null;
        $basePhone = $validated['phone'];

        // ✅ Customer をメールアドレスで作成 / 更新
        $customer = null;
        if (!empty($baseEmail)) {
            $customer = Customer::updateOrCreate(
                ['email' => $baseEmail],
                [
                    'name'  => $baseName,
                    'phone' => $basePhone,
                ]
            );
        }

        try {
            $reservation = Reservation::create([
                'user_id'          => $user->id,
                'customer_id'      => $customer?->id,
                'service_id'       => $service->id,
                'name'             => $baseName,
                'email'            => $baseEmail,
                'phone'            => $basePhone,
                'date'             => $validated['date'],
                'start_time'       => $proposedStart->format('H:i:s'),
                'end_time'         => $proposedEnd->format('H:i:s'),
                'status'           => 'confirmed',
                'notes'            => $validated['notes'] ?? null,
                'reservation_code' => strtoupper(uniqid('RSV')),
            ]);
        } catch (QueryException $e) {
            // ★ DB ユニーク制約に引っかかった場合（あれば）
            if (isset($e->errorInfo[1]) && (int)$e->errorInfo[1] === 1062) {
                return back()
                    ->with('message', '選択された時間枠は既に他の予約で埋まっています。（DB制約）')
                    ->withInput();
            }

            Log::error('[マイページ予約登録エラー] ' . $e->getMessage(), [
                'user_id'    => $user->id ?? null,
                'date'       => $validated['date'] ?? null,
                'start_time' => $validated['start_time'] ?? null,
                'service_id' => $validated['service_id'] ?? null,
            ]);

            return back()
                ->with('message', '予約処理中にエラーが発生しました。')
                ->withInput();
        }

        $reservation->load('service');

        // ✅ 顧客統計のリフレッシュ
        if ($customer) {
            try {
                $customer->recalculateStats();
            } catch (\Throwable $e) {
                Log::warning('[顧客統計更新エラー]', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // ✅ リマインド & サンクスメールのスケジュール登録（既存運用に寄せる）
        try {
            $this->scheduleReservationEmails($reservation, $proposedStart);
        } catch (\Throwable $e) {
            Log::error('[予約メールスケジュール登録エラー] ' . $e->getMessage(), [
                'reservation_id' => $reservation->id ?? null,
            ]);
        }

        // ✅ 即時メール送信（既存運用に寄せる）
        try {
            if (!empty($reservation->email)) {
                Mail::to($reservation->email)->send(new ReservationConfirmedMail($reservation));
            }

            $adminEmail = env('MAIL_ADMIN_ADDRESS', 'admin@lash-brow-ohana.local');
            Mail::to($adminEmail)->send(new AdminReservationNoticeMail($reservation));
        } catch (\Throwable $e) {
            Log::error('[マイページ予約メール送信エラー]', [
                'reservation_id' => $reservation->id ?? null,
                'email' => $reservation->email ?? '不明',
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('mypage.index')
            ->with('success', '✅ ご予約が完了しました！メールをご確認ください。');
    }

    /**
     * ✅ BusinessHour から当日の open/close を解決する
     *
     * @return array{0: ?Carbon, 1: ?Carbon, 2: ?string}
     */
    protected function resolveOpenCloseByBusinessHour(Carbon $date): array
    {
        $year  = (int) $date->year;
        $month = (int) $date->month;

        // ✅ 月データが無い場合はデフォルトを自動生成（既存運用に合わせる）
        if (BusinessHour::where('year', $year)->where('month', $month)->count() === 0) {
            BusinessHour::seedDefaultForMonth($year, $month);
        }

        $week  = BusinessHour::getWeekOfMonth($date);
        $dayJa = ['日','月','火','水','木','金','土'][$date->dayOfWeek];

        $bh = BusinessHour::where('year', $year)
            ->where('month', $month)
            ->where('week_of_month', $week)
            ->where('day_of_week', $dayJa)
            ->first();

        if (!$bh) {
            return [null, null, '営業時間が未設定です。'];
        }

        if ($bh->is_closed) {
            return [null, null, '本日は休業日です。'];
        }

        $openStr  = BusinessHour::normalizeTimeToHi($bh->open_time);
        $closeStr = BusinessHour::normalizeTimeToHi($bh->close_time);

        if (!$openStr || !$closeStr) {
            return [null, null, '営業時間が未設定です。'];
        }

        $open  = Carbon::parse($date->format('Y-m-d') . ' ' . $openStr);
        $close = Carbon::parse($date->format('Y-m-d') . ' ' . $closeStr);

        if ($close->lte($open)) {
            return [null, null, '営業時間の設定が不正です。'];
        }

        return [$open, $close, null];
    }

    /**
     * 予約日時を基準に、リマインド／サンクスメールを scheduled_emails に登録する
     *
     * - リマインド：2日前 + 前日
     * - サンクス：3日後
     * - 再来店促進：1か月後
     */
    protected function scheduleReservationEmails(Reservation $reservation, Carbon $startDateTime): void
    {
        $email  = $reservation->email;
        $userId = $reservation->user_id;

        if (empty($email)) {
            return;
        }

        $this->createScheduleEntry(
            $reservation,
            $userId,
            $email,
            'reservation_reminder_2days',
            $startDateTime->copy()->subDays(2)
        );

        $this->createScheduleEntry(
            $reservation,
            $userId,
            $email,
            'reservation_reminder_1day',
            $startDateTime->copy()->subDay()
        );

        $this->createScheduleEntry(
            $reservation,
            $userId,
            $email,
            'reservation_thanks_3days',
            $startDateTime->copy()->addDays(3)
        );

        $this->createScheduleEntry(
            $reservation,
            $userId,
            $email,
            'reservation_thanks_1month',
            $startDateTime->copy()->addMonth()
        );
    }

    /**
     * scheduled_emails テーブルへ1件登録する
     *
     * ※ send_at がすでに現在時刻を過ぎている場合はスキップ（デバッグ時の暴走防止）
     */
    protected function createScheduleEntry(
        Reservation $reservation,
        ?int $userId,
        string $email,
        string $type,
        Carbon $sendAt
    ): void {
        if ($sendAt->lte(now())) {
            return;
        }

        ScheduledEmail::create([
            'user_id'      => $userId,
            'email'        => $email,
            'type'         => $type,
            'related_type' => Reservation::class,
            'related_id'   => $reservation->id,
            'send_at'      => $sendAt,
            'status'       => 'pending',
        ]);
    }
}
