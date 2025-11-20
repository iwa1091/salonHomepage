<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReservationController extends Controller
{
    /**
     * 予約フォーム表示（Inertia）
     */
    public function form(Request $request)
    {
        return Inertia::render('Reservation/ReservationForm', [
            'service_id' => $request->service_id ?? null,
        ]);
    }

    /**
     * 一般ユーザーの予約登録（user_id 対応版）
     */
    public function store(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'name'       => 'nullable|string',
            'email'      => 'nullable|email',
            'date'       => 'required|date',
            'start_time' => 'required',
            'end_time'   => 'required',
            'notes'      => 'nullable|string|max:500',
        ]);

        // ログインユーザー情報（ログインしていない場合は null）
        $user = Auth::user();

        $reservation = Reservation::create([
            'service_id' => $request->service_id,
            'date'       => $request->date,
            'start_time' => $request->start_time,
            'end_time'   => $request->end_time,
            'notes'      => $request->notes,

            // 🔹 ログインユーザーなら自動セット
            'user_id' => $user ? $user->id : null,

            // 🔹 ゲスト予約用（メールフォームからの名前・メール）
            'name'  => $user ? $user->name  : $request->name,
            'email' => $user ? $user->email : $request->email,

            // 初期ステータス
            'status' => 'confirmed',
        ]);

        return redirect()
            ->route('mypage.index')
            ->with('success', 'ご予約を受け付けました！');
    }
}
