<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Service;      // 使う可能性があるので残しておく
use App\Models\BusinessHour;
use App\Models\Customer;     // 顧客モデル
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;


class AdminReservationController extends Controller
{
    /**
     * -------------------------------------------------------------
     * 🖥 管理画面（Inertia）用：予約一覧
     * -------------------------------------------------------------
     */
    public function index(Request $request)
    {
        $reservations = Reservation::with(['service', 'user'])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(15)
            ->through(fn ($r) => [
                'id'            => $r->id,
                'date'          => $r->date,
                'start_time'    => $r->start_time,
                'end_time'      => $r->end_time,
                'name'          => $r->name,
                'email'         => $r->email,
                'status'        => $r->status,
                'notes'         => $r->notes,
                'service_name'  => $r->service?->name,
                'duration'      => $r->service?->duration_minutes,  // 所要時間も表示
                'user_id'       => $r->user_id,
                'user_name'     => $r->user?->name,
            ]);

        return Inertia::render('Admin/ReservationList', [
            'reservations' => $reservations,
        ]);
    }

    /**
     * -------------------------------------------------------------
     * 🟦 API用：予約一覧（React管理画面の fetch 用）
     * GET /api/admin/reservations
     * -------------------------------------------------------------
     */
    public function apiIndex(Request $request)
    {
        // ✅ 追加：from/to（ReservationList.jsx が付与）に対応して絞り込む
        // ✅ 追加：デフォルトではキャンセルを除外（confirmed のみ）
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to'   => ['nullable', 'date_format:Y-m-d'],
            // 任意：キャンセルも含めたい場合のみ ?include_canceled=1
            'include_canceled' => ['nullable', 'boolean'],
        ]);

        $query = Reservation::with(['service', 'user'])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc');

        if (!empty($validated['from'])) {
            $query->where('date', '>=', $validated['from']);
        }
        if (!empty($validated['to'])) {
            $query->where('date', '<=', $validated['to']);
        }

        $includeCanceled = (bool)($validated['include_canceled'] ?? false);
        if (!$includeCanceled) {
            $query->where('status', 'confirmed');
        }

        $reservations = $query
            ->get()
            ->map(fn ($r) => [
                'id'            => $r->id,
                'date'          => $r->date,
                'start_time'    => $r->start_time,
                'end_time'      => $r->end_time,
                'name'          => $r->name,
                'email'         => $r->email,
                'status'        => $r->status,
                'notes'         => $r->notes,
                'service_name'  => $r->service?->name,
                'duration'      => $r->service?->duration_minutes, // 所要時間も返す
                'user_id'       => $r->user_id,
                'user_name'     => $r->user?->name,
            ]);

        return response()->json($reservations);
    }

    /**
     * -------------------------------------------------------------
     * 🟦 API用：予約削除
     * DELETE /api/admin/reservations/{id}
     * -------------------------------------------------------------
     */
    public function apiDestroy($id)
    {
        $reservation = Reservation::findOrFail($id);

        // 削除前に顧客IDを控えておく
        $customerId = $reservation->customer_id;

        $reservation->delete();

        // 紐づく顧客の統計情報を再計算
        if ($customerId) {
            $customer = Customer::find($customerId);
            if ($customer) {
                $customer->recalculateStats();
            }
        }

        return response()->json([
            'message' => '予約を削除しました',
        ]);
    }

    /**
     * -------------------------------------------------------------
     * ✏️ 管理画面：予約編集ページ
     * -------------------------------------------------------------
     */
    public function edit($id)
    {
        // 予約情報 + サービス・ユーザーを取得
        $reservation = Reservation::with(['service', 'user'])->findOrFail($id);

        // ✅ 予約日の年月に合わせて営業時間（business_hours）を取得
        //    データが無ければ seed してから取得する
        $targetDate = Carbon::parse($reservation->date);
        $year  = (int) $targetDate->year;
        $month = (int) $targetDate->month;

        if (BusinessHour::where('year', $year)->where('month', $month)->count() === 0) {
            BusinessHour::seedDefaultForMonth($year, $month);
        }

        $businessHours = BusinessHour::where('year', $year)
            ->where('month', $month)
            ->orderBy('week_of_month')
            ->orderByRaw("FIELD(day_of_week, '月','火','水','木','金','土','日')")
            ->get();

        return Inertia::render('Admin/ReservationEdit', [
            'reservation' => [
                'id'           => $reservation->id,
                'date'         => $reservation->date,
                'start_time'   => $reservation->start_time,
                'end_time'     => $reservation->end_time,
                'name'         => $reservation->name,
                'email'        => $reservation->email,
                'phone'        => $reservation->phone, // ✅ 追加
                'status'       => $reservation->status,
                'notes'        => $reservation->notes,

                'service_id'   => $reservation->service_id,
                'service_name' => $reservation->service?->name,
                'duration'     => $reservation->service?->duration_minutes,

                'service' => $reservation->service ? [
                    'id'               => $reservation->service->id,
                    'name'             => $reservation->service->name,
                    'duration_minutes' => $reservation->service->duration_minutes,
                ] : null,

                'user_id'   => $reservation->user_id,
                'user_name' => $reservation->user?->name,
                'user'      => $reservation->user ? [
                    'id'   => $reservation->user->id,
                    'name' => $reservation->user->name,
                ] : null,
            ],

            // ✅ これで未定義エラーが消え、props として渡せます
            'businessHours' => $businessHours,
        ]);
    }

    /**
     * -------------------------------------------------------------
     * ♻️ 管理画面：予約更新
     *   PUT /admin/reservations/{id}
     *   route name: admin.reservations.update
     * -------------------------------------------------------------
     */
    public function update(Request $request, $id)
    {
        $reservation = Reservation::findOrFail($id);

        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['nullable', 'email', 'max:255'],     // ✅ 追加
            'phone'      => ['nullable', 'string', 'max:20'],     // ✅ 追加
            'notes'      => ['nullable', 'string', 'max:1000'],   // ✅ 追加（上限は運用に合わせて）
            'date'       => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'service_id' => ['required', 'exists:services,id'],
        ]);

        $service = Service::findOrFail($validated['service_id']);

        $startDateTime = Carbon::createFromFormat(
            'Y-m-d H:i',
            $validated['date'] . ' ' . $validated['start_time']
        );
        $endDateTime = (clone $startDateTime)->addMinutes($service->duration_minutes);

        $isOverlapping = Reservation::where('date', $validated['date'])
            ->where('status', 'confirmed')
            ->where('id', '!=', $reservation->id)
            ->where(function ($query) use ($startDateTime, $endDateTime) {
                $query->where('start_time', '<', $endDateTime->format('H:i:s'))
                    ->where('end_time', '>', $startDateTime->format('H:i:s'));
            })
            ->exists();

        if ($isOverlapping) {
            return back()
                ->withErrors(['start_time' => '指定された時間帯は他の予約と重複しています。'])
                ->withInput();
        }

        $reservation->update([
            'name'       => $validated['name'],
            'email'      => $validated['email'] ?? null,  // ✅ 追加
            'phone'      => $validated['phone'] ?? null,  // ✅ 追加
            'notes'      => $validated['notes'] ?? null,  // ✅ 追加
            'date'       => $validated['date'],
            'start_time' => $startDateTime->format('H:i:s'),
            'end_time'   => $endDateTime->format('H:i:s'),
            'service_id' => $validated['service_id'],
        ]);

        if ($reservation->customer_id) {
            $customer = Customer::find($reservation->customer_id);
            if ($customer) {
                $customer->recalculateStats();
            }
        }

        return redirect()
            ->route('admin.reservations.index')
            ->with('success', '予約内容を更新しました');
    }


    /**
     * -------------------------------------------------------------
     * 🗑 Inertia 用の削除
     * -------------------------------------------------------------
     */
    public function destroy($id)
    {
        $reservation = Reservation::findOrFail($id);
        $customerId  = $reservation->customer_id;

        $reservation->delete();

        // 紐づく顧客の統計情報を再計算
        if ($customerId) {
            $customer = Customer::find($customerId);
            if ($customer) {
                $customer->recalculateStats();
            }
        }

        return redirect()
            ->route('admin.reservations.index')
            ->with('success', '予約を削除しました');
    }

    /**
     * -------------------------------------------------------------
     * ドラッグリサイズ用：時間のみ更新
     * PUT /admin/api/reservations/{id}
     * -------------------------------------------------------------
     */
    public function updateTime(Request $request, $id)
    {
        $reservation = Reservation::findOrFail($id);

        $v = $request->validate([
            'date'             => ['required', 'date'],
            'start_time'       => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:600'],
        ]);

        if ($v['duration_minutes'] % 15 !== 0) {
            return response()->json(['message' => 'duration_minutes は15分刻みで指定してください'], 422);
        }

        $start = Carbon::createFromFormat('Y-m-d H:i', $v['date'] . ' ' . $v['start_time']);
        $end   = (clone $start)->addMinutes((int) $v['duration_minutes']);

        $reservation->update([
            'date'       => $v['date'],
            'start_time' => $start->format('H:i:s'),
            'end_time'   => $end->format('H:i:s'),
        ]);

        return response()->json([
            'id'         => $reservation->id,
            'date'       => $reservation->date,
            'start_time' => $reservation->start_time,
            'end_time'   => $reservation->end_time,
        ]);
    }
}
