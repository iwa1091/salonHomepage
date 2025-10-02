<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\Schedule;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * 一般ユーザー向けの予約および空き時間チェックAPIを管理するコントローラー
 */
class ReservationController extends Controller
{
    /**
     * 指定されたサービスと日付に基づいて、予約可能な空き時間を計算して返します。
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAvailability(Request $request)
    {
        // 1. バリデーション
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'service_id' => 'required|exists:services,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $date = Carbon::parse($request->date);
        $service = Service::find($request->service_id);
        $duration = $service->duration_minutes; // 予約に必要な所要時間（分）

        // 2. 当日の営業時間 (Schedule) を取得
        // まず例外スケジュール（exception）を確認し、存在しなければ基本スケジュール（weekly）を使用
        $schedule = Schedule::exception($date)->first();
        if (!$schedule) {
            $schedule = Schedule::weekly($date)
                ->where('day_of_week', $date->dayOfWeek)
                ->first();
        }

        // 営業時間の設定がない（終日休業）
        if (!$schedule || !$schedule->start_time || !$schedule->end_time) {
            return response()->json(['available_slots' => [], 'message' => '本日は終日休業です。'], 200);
        }

        // 営業時間（Carbonインスタンスに変換）
        $openTime = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->start_time->format('H:i'));
        $closeTime = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->end_time->format('H:i'));

        // 3. 予約済みの時間枠を取得
        // 確定済みの予約のみを対象とする
        $bookedSlots = Reservation::where('date', $date->format('Y-m-d'))
                                    ->where('status', 'confirmed') 
                                    ->get(['start_time', 'end_time'])
                                    ->map(function ($reservation) use ($date) {
                                        return [
                                            'start' => Carbon::parse($date->format('Y-m-d') . ' ' . $reservation->start_time),
                                            'end' => Carbon::parse($date->format('Y-m-d') . ' ' . $reservation->end_time),
                                        ];
                                    })->toArray();
        
        // 4. 空き時間枠の計算
        $availableSlots = [];
        $currentTime = clone $openTime;

        // 現在時刻が閉店時刻より前である限りループ
        while ($currentTime->lt($closeTime)) {
            // 予約枠の終了時刻を計算
            $slotEnd = (clone $currentTime)->addMinutes($duration);

            // 閉店時刻を超過する場合は、そのスロットは提供しない
            if ($slotEnd->gt($closeTime)) {
                break;
            }

            $isBooked = false;

            // 既存の予約と重複していないかチェック
            foreach ($bookedSlots as $booked) {
                // 予約開始時間 <= 現在のスロット開始時間 < 予約終了時間 (スロット開始が予約と重複)
                // または
                // 予約開始時間 < 現在のスロット終了時間 <= 予約終了時間 (スロット終了が予約と重複)
                // このロジックでは、既存の予約の開始・終了時刻に「ぴったり」合致する予約は許容されますが、
                // 間に割り込む予約を防ぎます。
                if (($currentTime->gte($booked['start']) && $currentTime->lt($booked['end'])) || 
                    ($slotEnd->gt($booked['start']) && $slotEnd->lte($booked['end'])) ||
                    ($currentTime->lt($booked['start']) && $slotEnd->gt($booked['end'])) // 既存の予約を完全に覆い隠す場合
                ) {
                    $isBooked = true;
                    // 重複が見つかった場合、現在の予約の終了時刻から再開する
                    // これにより、次のチェックをスキップし、効率化を図る
                    $currentTime = clone $booked['end'];
                    break; 
                }
            }

            if (!$isBooked) {
                // 予約されていない空きスロットを追加
                $availableSlots[] = [
                    'start' => $currentTime->format('H:i'),
                    'end' => $slotEnd->format('H:i'),
                ];
                // 次の空き時間チェックへ進む
                $currentTime = $slotEnd;
            }
            
            // Note: $duration分の時間の加算は、if/elseブロック内で処理されます。
            // 重複があった場合は$booked['end']へジャンプし、なければ$slotEnd（現在のスロット終了時刻）へジャンプします。
        }

        return response()->json(['available_slots' => $availableSlots], 200);
    }

    /**
     * 予約を新規作成します。
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'service_id' => 'required|exists:services,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'notes' => 'nullable|string',
            // 'user_id' はauth:sanctumを使っていれば自動で取得できますが、
            // ゲスト予約を許容するため、ここではバリデーションしない
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $service = Service::find($request->service_id);
        $duration = $service->duration_minutes;

        // 1. 重複予約チェック (重要なセキュリティ対策)
        // checkAvailabilityで行ったロジックを再実行して、このスロットが有効か確認すべきですが、
        // 簡略化のため、ここでは既存の予約との重複のみを確認します。
        $proposedStart = Carbon::parse($request->date . ' ' . $request->start_time);
        $proposedEnd = (clone $proposedStart)->addMinutes($duration);

        $isOverlapping = Reservation::where('date', $request->date)
            ->where('status', 'confirmed')
            ->where(function ($query) use ($proposedStart, $proposedEnd) {
                // 既存の予約の開始時間と終了時間をチェック
                $query->where('start_time', '<', $proposedEnd->format('H:i:s')) // 既存の予約の開始が提案終了より前
                      ->where('end_time', '>', $proposedStart->format('H:i:s')); // 既存の予約の終了が提案開始より後
            })
            ->exists();

        if ($isOverlapping) {
            return response()->json(['message' => '選択された時間枠は既に予約済みか、無効です。'], 409);
        }

        // 2. 予約データの作成
        $reservation = Reservation::create([
            'user_id' => $request->user() ? $request->user()->id : null, // ログインユーザーがいればIDを使用
            'service_id' => $request->service_id,
            'name' => $request->name,
            'email' => $request->email,
            'date' => $request->date,
            'start_time' => $proposedStart->format('H:i:s'),
            'end_time' => $proposedEnd->format('H:i:s'),
            'status' => 'confirmed', // 外部認証（メール確認など）がないため、ここでは即時確定(confirmed)とします
            'notes' => $request->notes,
        ]);

        // 3. 予約完了メール送信などの追加ロジック...

        return response()->json(['message' => '予約が完了しました。', 'reservation' => $reservation], 201);
    }
}
