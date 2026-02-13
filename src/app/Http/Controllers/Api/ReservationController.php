<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\BusinessHour;             // ✅ 追加：営業時間（BusinessHour）を正にする
use App\Models\Customer;                // 顧客モデル
use App\Models\ScheduledEmail;          // 予約メールスケジュールモデル
use App\Models\AdminBlock;              // ✅ 追加：管理者ブロック（枠2/枠3）
use App\Http\Requests\StoreReservationRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReservationConfirmedMail;
use App\Mail\AdminReservationNoticeMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

/**
 * 一般ユーザー向けの予約および空き時間チェックAPIを管理するコントローラー
 *
 * ✅ 営業時間の判定は BusinessHour を正として扱う
 */
class ReservationController extends Controller
{
    /**
     * 🔍 予約可能時間の確認（BusinessHour 基準）
     *
     * - 15分刻みで開始時刻候補を生成
     * - サービス所要時間（duration）ぶんの枠が営業時間内に収まるものだけ
     * - 既存予約（confirmed）と重複するものは除外
     * - 現在時刻から12時間以内の枠は除外（ワンオペ運用）
     *
     * ✅ 追加：
     * - 管理者ブロック（lane=2）の時間帯も除外（lane=3 は現状のまま＝判定に含めない）
     *
     * ✅ 変更（要望対応）：
     * - 「重なり判定」を **開始時刻が busy に入っているか** のみに変更
     *   （busy の end も含める：12:00-13:00 の場合 13:00 も×）
     */
    public function checkAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date'       => 'required|date_format:Y-m-d|after_or_equal:today',
            'service_id' => 'required|exists:services,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $date     = Carbon::parse($request->date);
        $service  = Service::find($request->service_id);
        $duration = (int) ($service->duration_minutes ?? 30);

        // ✅ BusinessHour を取得（なければ休業扱い）
        [$openTime, $closeTime, $bhMessage] = $this->resolveOpenCloseByBusinessHour($date);

        if (!$openTime || !$closeTime) {
            return response()->json([
                'available_slots' => [],
                'message'         => $bhMessage ?: '本日は終日休業です。',
            ], 200);
        }

        // ✅ 予約済み時間帯を取得（当日 confirmed のみ）
        $bookedSlots = Reservation::where('date', $date->format('Y-m-d'))
            ->where('status', 'confirmed')
            ->get(['start_time', 'end_time'])
            ->map(function ($r) use ($date) {
                // ✅ start_time / end_time が「time」でも「datetime」でも壊れないように、
                //    "当日の date + 時刻" の Carbon に正規化する
                return [
                    'start' => $this->normalizeTimeOnDate($date, $r->start_time),
                    'end'   => $this->normalizeTimeOnDate($date, $r->end_time),
                ];
            })
            ->toArray();

        // ✅ 追加：管理者ブロック（lane=2）も「埋まり」として扱う（lane=3 は現状維持のため除外）
        $blockedSlots = AdminBlock::where('date', $date->format('Y-m-d'))
            ->where('lane', 2)
            ->get(['start_time', 'end_time'])
            ->map(function ($b) use ($date) {
                return [
                    'start' => $this->normalizeTimeOnDate($date, $b->start_time),
                    'end'   => $this->normalizeTimeOnDate($date, $b->end_time),
                ];
            })
            ->toArray();

        // 予約済み + ブロック済み をまとめる
        $busySlots = array_merge($bookedSlots, $blockedSlots);

        $availableSlots = [];

        // ✅ 12時間ルール（開始時刻がこれ未満は候補から外す）
        $minStart = now()->addHours(12);

        // ✅ 開始時刻候補（15分刻み）を生成
        $currentTime = $this->alignToQuarterHour($openTime->copy());

        while ($currentTime->lt($closeTime)) {
            $slotEnd = (clone $currentTime)->addMinutes($duration);

            // 所要時間ぶんが営業時間を超えるなら終了
            if ($slotEnd->gt($closeTime)) {
                break;
            }

            // 12時間以内は不可
            if ($currentTime->lt($minStart)) {
                $currentTime->addMinutes(15);
                continue;
            }

            // ✅ 変更：開始時刻が busy（予約/ブロック）の時間帯に入っていれば×（endも含める）
            $isBooked = collect($busySlots)->contains(function ($busy) use ($currentTime) {
                return $currentTime->gte($busy['start']) && $currentTime->lte($busy['end']);
            });

            if (!$isBooked) {
                $availableSlots[] = [
                    'start' => $currentTime->format('H:i'),
                    'end'   => $slotEnd->format('H:i'),
                ];
            }

            // ✅ 15分刻みで次の開始候補へ
            $currentTime->addMinutes(15);
        }

        return response()->json(['available_slots' => $availableSlots], 200);
    }

    /**
     * 📨 予約作成 + メール送信（MailHog対応）
     *
     * ✅ 追加：
     * - BusinessHour（営業時間）内かチェック
     * - 12時間以内の予約を禁止
     * - 15分刻み以外の開始時刻を禁止（UIと整合）
     *
     * ✅ 追加：
     * - 管理者ブロック（lane=2）と重複する予約を禁止（lane=3 は現状のまま＝判定に含めない）
     *
     * ✅ 変更（要望対応）：
     * - 「重なり判定」を **開始時刻が busy に入っているか** のみに変更
     *   （busy の end も含める：12:00-13:00 の場合 13:00 開始も×）
     */
    public function store(StoreReservationRequest $request)
    {
        $service  = Service::find($request->service_id);
        $duration = (int) ($service->duration_minutes ?? 30);

        $proposedStart = Carbon::parse($request->date . ' ' . $request->start_time);
        $proposedEnd   = (clone $proposedStart)->addMinutes($duration);

        // ✅ 15分刻みチェック（分が 0/15/30/45 以外は弾く）
        if (((int) $proposedStart->format('i')) % 15 !== 0) {
            return response()->json([
                'message' => '開始時刻は15分刻みで選択してください。',
            ], 422);
        }

        // ✅ 12時間ルール
        $minStart = now()->addHours(12);
        if ($proposedStart->lt($minStart)) {
            return response()->json([
                'message' => 'ご予約は現在時刻から12時間以降の枠のみ受付可能です。',
            ], 422);
        }

        // ✅ BusinessHour（営業時間）チェック
        [$openTime, $closeTime, $bhMessage] = $this->resolveOpenCloseByBusinessHour(Carbon::parse($request->date));

        if (!$openTime || !$closeTime) {
            return response()->json([
                'message' => $bhMessage ?: '本日は終日休業のため予約できません。',
            ], 422);
        }

        if ($proposedStart->lt($openTime) || $proposedEnd->gt($closeTime)) {
            return response()->json([
                'message' => '選択された時間は営業時間外です。',
            ], 422);
        }

        // ✅ 変更：開始時刻が既存予約の時間帯に入っていればNG（endも含める）
        $startT = $proposedStart->format('H:i:s');

        $isOverlapping = Reservation::where('date', $request->date)
            ->where('status', 'confirmed')
            ->where('start_time', '<=', $startT)
            ->where('end_time',   '>=', $startT)
            ->exists();

        if ($isOverlapping) {
            return response()->json([
                'message' => '選択された時間枠は既に予約済みです。',
            ], 409);
        }

        // ✅ 変更：管理者ブロック（lane=2）も、開始時刻がブロック時間帯に入っていればNG（endも含める）
        $isBlockedByLane2 = AdminBlock::where('date', $request->date)
            ->where('lane', 2)
            ->where('start_time', '<=', $startT)
            ->where('end_time',   '>=', $startT)
            ->exists();

        if ($isBlockedByLane2) {
            return response()->json([
                'message' => '選択された時間枠はブロック設定により予約できません。',
            ], 409);
        }

        // 🔹 ログインユーザー（いれば）
        $user = $request->user();

        // 🔹 顧客情報のベース（User 優先・いなければリクエストから）
        $baseName  = $user ? $user->name  : $request->name;
        $baseEmail = $user ? $user->email : $request->email;
        $basePhone = $user ? $user->phone : $request->phone;

        // 🔹 Customer をメールアドレスで作成 / 更新
        $customer = null;
        if ($baseEmail) {
            $customer = Customer::updateOrCreate(
                ['email' => $baseEmail],
                [
                    'name'  => $baseName,
                    'phone' => $basePhone,
                ]
            );
        }

        // 💾 データベース登録
        try {
            $reservation = Reservation::create([
                'user_id'          => $user?->id,
                'customer_id'      => $customer?->id,
                'service_id'       => $request->service_id,
                'name'             => $baseName,
                'email'            => $baseEmail,
                'phone'            => $basePhone,
                'date'             => $request->date,
                'start_time'       => $proposedStart->format('H:i:s'),
                'end_time'         => $proposedEnd->format('H:i:s'),
                'status'           => 'confirmed',
                'notes'            => $request->notes,
                'reservation_code' => strtoupper(uniqid('RSV')),
            ]);
        } catch (QueryException $e) {
            // ★ DB ユニーク制約に引っかかった場合
            if (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1062) {
                return response()->json([
                    'message' => '選択された時間枠は既に他の予約で埋まっています。（DB制約）',
                ], 409);
            }

            Log::error('[予約登録エラー] ' . $e->getMessage(), [
                'date'       => $request->date,
                'start_time' => $request->start_time,
                'service_id' => $request->service_id,
            ]);

            return response()->json([
                'message' => '予約処理中にエラーが発生しました。',
            ], 500);
        }

        $reservation->load('service');

        // 🔹 顧客統計のリフレッシュ
        if ($customer) {
            $customer->recalculateStats();
        }

        // 🔔 リマインド & サンクスメールのスケジュール登録
        try {
            $this->scheduleReservationEmails($reservation, $proposedStart);
        } catch (\Throwable $e) {
            Log::error('[予約メールスケジュール登録エラー] ' . $e->getMessage(), [
                'reservation_id' => $reservation->id ?? null,
            ]);
        }

        // ✉️ 即時メール送信
        try {
            Mail::to($reservation->email)->send(new ReservationConfirmedMail($reservation));

            $adminEmail = env('MAIL_ADMIN_ADDRESS', 'admin@lash-brow-ohana.local');
            Mail::to($adminEmail)->send(new AdminReservationNoticeMail($reservation));

            // ✅ Mail::failures() は現在の Laravel / Mailer では存在しないため削除
        } catch (\Exception $e) {
            Log::error('[メール送信エラー] ' . $e->getMessage(), [
                'reservation_id' => $reservation->id ?? null,
                'email'          => $reservation->email ?? null,
            ]);
        }

        return response()->json([
            'message'     => '予約が完了しました（確認メールを送信しました）。',
            'reservation' => $reservation,
        ], 201);
    }

    /**
     * 📋 管理者向け一覧API
     */
    public function index()
    {
        $reservations = Reservation::with('service')
            ->orderBy('date', 'desc')
            ->get()
            ->map(fn ($r) => [
                'id'           => $r->id,
                'name'         => $r->name,
                'service_name' => $r->service->name ?? '未設定',
                'date'         => $r->date,
                'start_time'   => $r->start_time,
                'status'       => $r->status ?? '予約中',
            ]);

        return response()->json($reservations);
    }

    /**
     * ❌ 管理者用：予約削除API
     */
    public function destroy($id)
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json(['message' => '予約が見つかりません。'], 404);
        }

        $reservation->delete();

        return response()->json(['message' => '削除しました。'], 200);
    }

    /* =====================================================
     * 🔔 予約メールスケジュール登録関連（private/protected）
     * ===================================================== */

    /**
     * ✅ BusinessHour から当日の open/close を解決する
     *
     * @return array{0: ?Carbon, 1: ?Carbon, 2: ?string}
     */
    protected function resolveOpenCloseByBusinessHour(Carbon $date): array
    {
        $year  = (int) $date->year;
        $month = (int) $date->month;

        // ✅ 月データが無い場合はデフォルトを自動生成（運用に合わせて不要なら削除してOK）
        if (BusinessHour::where('year', $year)->where('month', $month)->count() === 0) {
            BusinessHour::seedDefaultForMonth($year, $month);
        }

        $week = BusinessHour::getWeekOfMonth($date);
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
     * ✅ Carbon を次の15分境界に揃える（09:07 -> 09:15）
     */
    protected function alignToQuarterHour(Carbon $dt): Carbon
    {
        $minute = (int) $dt->format('i');
        $mod = $minute % 15;

        if ($mod !== 0) {
            $dt->addMinutes(15 - $mod);
        }

        return $dt->setSecond(0);
    }

    /**
     * ✅ "指定日(date) + 時刻(start_time/end_time)" を安全に Carbon 化する
     *
     * - start_time/end_time が "09:00:00" のような time でも
     * - "2026-02-02 09:00:00" のような datetime でも
     *   → 当日の date に揃えて "YYYY-mm-dd HH:ii:ss" として解釈する
     *
     * これにより "2026-02-04 2026-02-02 09:00:00" のような二重日付を防ぐ。
     */
    protected function normalizeTimeOnDate(Carbon $date, $timeValue): Carbon
    {
        // DateTime/Carbon が来た場合も "時刻だけ" を抽出して当日付けに揃える
        if ($timeValue instanceof \DateTimeInterface) {
            $time = Carbon::instance($timeValue)->format('H:i:s');
            return Carbon::parse($date->format('Y-m-d') . ' ' . $time);
        }

        $str = trim((string) $timeValue);

        if ($str === '') {
            // 想定外の空値（念のため）
            return Carbon::parse($date->format('Y-m-d') . ' 00:00:00');
        }

        // "HH:MM" or "HH:MM:SS"
        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $str)) {
            if (strlen($str) === 5) {
                $str .= ':00';
            }
            return Carbon::parse($date->format('Y-m-d') . ' ' . $str);
        }

        // それ以外（datetime文字列など）は parse して時刻部分だけ使う
        try {
            $parsed = Carbon::parse($str);
            $time   = $parsed->format('H:i:s');
            return Carbon::parse($date->format('Y-m-d') . ' ' . $time);
        } catch (\Throwable $e) {
            Log::warning('[normalizeTimeOnDate] start/end_time のパースに失敗しました。', [
                'date'      => $date->format('Y-m-d'),
                'timeValue' => $timeValue,
                'error'     => $e->getMessage(),
            ]);

            return Carbon::parse($date->format('Y-m-d') . ' 00:00:00');
        }
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
