<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;
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
                'id'          => $r->id,
                'date'        => $r->date,
                'start_time'  => $r->start_time,
                'end_time'    => $r->end_time,
                'name'        => $r->name,
                'email'       => $r->email,
                'status'      => $r->status,
                'notes'       => $r->notes,
                'service_name'=> $r->service?->name,
                'user_id'     => $r->user_id,
                'user_name'   => $r->user?->name,
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
    public function apiIndex()
    {
        $reservations = Reservation::with(['service', 'user'])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(fn ($r) => [
                'id'          => $r->id,
                'date'        => $r->date,
                'start_time'  => $r->start_time,
                'end_time'    => $r->end_time,
                'name'        => $r->name,
                'email'       => $r->email,
                'status'      => $r->status,
                'notes'       => $r->notes,
                'service_name'=> $r->service?->name,
                'user_id'     => $r->user_id,
                'user_name'   => $r->user?->name,
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
        Reservation::findOrFail($id)->delete();

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
        $reservation = Reservation::with(['service', 'user'])
            ->findOrFail($id);

        return Inertia::render('Admin/ReservationEdit', [
            'reservation' => [
                'id'           => $reservation->id,
                'date'         => $reservation->date,
                'start_time'   => $reservation->start_time,
                'end_time'     => $reservation->end_time,
                'name'         => $reservation->name,
                'email'        => $reservation->email,
                'status'       => $reservation->status,
                'notes'        => $reservation->notes,
                'service_id'   => $reservation->service_id,
                'service_name' => $reservation->service?->name,
                'user_id'      => $reservation->user_id,
                'user_name'    => $reservation->user?->name,
            ],
        ]);
    }

    /**
     * -------------------------------------------------------------
     * 🗑 Inertia 用の削除
     * -------------------------------------------------------------
     */
    public function destroy($id)
    {
        Reservation::findOrFail($id)->delete();

        return redirect()
            ->route('admin.reservations.index')
            ->with('success', '予約を削除しました');
    }
}
