<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\Schedule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReservationConfirmedMail;
use App\Mail\AdminReservationNoticeMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 一般ユーザー向けの予約および空き時間チェックAPIを管理するコントローラー
 */
class ReservationController extends Controller
{
    /**
     * 🔍 予約可能時間の確認
     */
    public function checkAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'service_id' => 'required|exists:services,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $date = Carbon::parse($request->date);
        $service = Service::find($request->service_id);
        $duration = $service->duration_minutes ?? 30;

        // 例外スケジュール優先 → なければ通常スケジュール
        $schedule = Schedule::exception($date)->first()
            ?? Schedule::weekly($date)->where('day_of_week', $date->dayOfWeek)->first();

        if (!$schedule || !$schedule->start_time || !$schedule->end_time) {
            return response()->json(['available_slots' => [], 'message' => '本日は終日休業です。'], 200);
        }

        $openTime = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->start_time->format('H:i'));
        $closeTime = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->end_time->format('H:i'));

        // 予約済み時間帯を取得
        $bookedSlots = Reservation::where('date', $date->format('Y-m-d'))
            ->where('status', 'confirmed')
            ->get(['start_time', 'end_time'])
            ->map(function ($r) use ($date) {
                return [
                    'start' => Carbon::parse($date->format('Y-m-d') . ' ' . $r->start_time),
                    'end'   => Carbon::parse($date->format('Y-m-d') . ' ' . $r->end_time),
                ];
            })->toArray();

        $availableSlots = [];
        $currentTime = clone $openTime;

        while ($currentTime->lt($closeTime)) {
            $slotEnd = (clone $currentTime)->addMinutes($duration);
            if ($slotEnd->gt($closeTime)) break;

            $isBooked = collect($bookedSlots)->contains(function ($booked) use ($currentTime, $slotEnd) {
                return (
                    ($currentTime->gte($booked['start']) && $currentTime->lt($booked['end'])) ||
                    ($slotEnd->gt($booked['start']) && $slotEnd->lte($booked['end'])) ||
                    ($currentTime->lt($booked['start']) && $slotEnd->gt($booked['end']))
                );
            });

            if (!$isBooked) {
                $availableSlots[] = [
                    'start' => $currentTime->format('H:i'),
                    'end'   => $slotEnd->format('H:i'),
                ];
            }

            $currentTime->addMinutes($duration);
        }

        return response()->json(['available_slots' => $availableSlots], 200);
    }

    /**
     * 📨 予約作成 + メール送信（MailHog対応）
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date'       => 'required|date_format:Y-m-d|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'service_id' => 'required|exists:services,id',
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|max:255',
            'notes'      => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $service = Service::find($request->service_id);
        $duration = $service->duration_minutes ?? 30;

        $proposedStart = Carbon::parse($request->date . ' ' . $request->start_time);
        $proposedEnd = (clone $proposedStart)->addMinutes($duration);

        // 🔁 重複予約チェック
        $isOverlapping = Reservation::where('date', $request->date)
            ->where('status', 'confirmed')
            ->where(function ($query) use ($proposedStart, $proposedEnd) {
                $query->where('start_time', '<', $proposedEnd->format('H:i:s'))
                      ->where('end_time', '>', $proposedStart->format('H:i:s'));
            })
            ->exists();

        if ($isOverlapping) {
            return response()->json(['message' => '選択された時間枠は既に予約済みです。'], 409);
        }

        // 💾 データベース登録
        $reservation = Reservation::create([
            'user_id'    => $request->user()?->id,
            'service_id' => $request->service_id,
            'name'       => $request->name,
            'email'      => $request->email,
            'date'       => $request->date,
            'start_time' => $proposedStart->format('H:i:s'),
            'end_time'   => $proposedEnd->format('H:i:s'),
            'status'     => 'confirmed',
            'notes'      => $request->notes,
            'reservation_code' => strtoupper(uniqid('RSV')),
        ]);

        $reservation->load('service');

        // ✉️ メール送信処理
        try {
            // 顧客宛
            Mail::to($reservation->email)->send(new ReservationConfirmedMail($reservation));

            // 管理者宛
            $adminEmail = env('MAIL_ADMIN_ADDRESS', 'admin@lash-brow-ohana.local');
            Mail::to($adminEmail)->send(new AdminReservationNoticeMail($reservation));

            if (count(Mail::failures()) > 0) {
                Log::warning('[メール送信失敗] 一部メール送信に失敗しました。', [
                    'reservation_id' => $reservation->id,
                    'failures' => Mail::failures(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('[メール送信エラー] ' . $e->getMessage(), [
                'reservation_id' => $reservation->id ?? null,
                'email' => $reservation->email ?? null,
            ]);
        }

        return response()->json([
            'message' => '予約が完了しました（確認メールを送信しました）。',
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
            ->map(fn($r) => [
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
}
