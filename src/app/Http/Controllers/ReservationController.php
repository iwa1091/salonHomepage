<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Mail\ReservationConfirmation;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ReservationController extends Controller
{
    // 一般ユーザー用
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|max:255',
            'phone'      => 'required|string|max:20',
            'menu'       => 'required|string|max:255',
            'date'       => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'notes'      => 'nullable|string',
        ]);

        $reservation = Reservation::create($data);

        // メール送信（Mailhog に送信）
        Mail::to($data['email'])->send(new ReservationConfirmation($reservation));

        // JSON レスポンスを返す (フロント側 fetch が処理可能)
        return response()->json([
            'message'     => '予約が完了しました',
            'reservation' => $reservation,
        ]);
    }

    // 管理画面用 一覧
    public function index()
    {
        $reservations = Reservation::all();
        return Inertia::render('Admin/ReservationList', compact('reservations'));
    }

    // 管理画面用 削除
    public function destroy($id)
    {
        Reservation::findOrFail($id)->delete();
        return redirect()->back();
    }
}
