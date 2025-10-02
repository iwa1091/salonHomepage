<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * 管理者向けの予約管理機能を担当するコントローラーです。
 * Inertiaを使用してReactコンポーネントをレンダリングします。
 */
class AdminReservationController extends Controller // ★ クラス名をAdminReservationControllerに修正
{
    /**
     * 予約一覧ページ（Admin/ReservationList.jsx）を表示します。
     *
     * @param \Illuminate\Http\Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        // 1. 予約データを取得 (N+1回避、ページネーション、整形を行う)
        $reservations = Reservation::with(['service', 'user'])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(15) // 1ページあたり15件
            ->through(function ($reservation) {
                // フロントエンドに渡す前に、必要なデータのみに整形します。
                return [
                    'id' => $reservation->id,
                    'date' => $reservation->date,
                    'start_time' => $reservation->start_time,
                    'end_time' => $reservation->end_time,
                    'name' => $reservation->name,
                    'email' => $reservation->email,
                    'status' => $reservation->status,
                    'notes' => $reservation->notes,
                    'service_name' => $reservation->service->name,
                    // ユーザー情報があれば、ユーザーIDと名前を渡します（ゲスト予約の場合はnull）
                    'user_id' => $reservation->user_id,
                    'user_name' => $reservation->user?->name,
                ];
            });

        // 2. InertiaでReactコンポーネントをレンダリング
        return Inertia::render('Admin/ReservationList', [
            'reservations' => $reservations,
        ]);
    }

    /**
     * 予約の詳細を表示または編集する画面（Admin/ReservationEdit.jsx）を表示します。
     *
     * @param int $id
     * @return \Inertia\Response
     */
    public function edit($id)
    {
        $reservation = Reservation::with(['service', 'user'])
            ->findOrFail($id);

        return Inertia::render('Admin/ReservationEdit', [
            'reservation' => [
                'id' => $reservation->id,
                'date' => $reservation->date,
                'start_time' => $reservation->start_time,
                'end_time' => $reservation->end_time,
                'name' => $reservation->name,
                'email' => $reservation->email,
                'status' => $reservation->status,
                'notes' => $reservation->notes,
                'service_id' => $reservation->service_id,
                'service_name' => $reservation->service->name,
                'user_id' => $reservation->user_id,
                'user_name' => $reservation->user?->name,
            ]
        ]);
    }

    /**
     * 予約を削除します。
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->delete();

        return redirect()->route('admin.reservations.index')
            ->with('success', '予約を削除しました');
    }
}
