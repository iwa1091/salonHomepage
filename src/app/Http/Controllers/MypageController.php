<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Reservation;
use App\Models\Order;

class MypageController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $pastReservations = Reservation::where('user_id', $user->id)
            ->where('date', '<', now()->toDateString())
            ->with('service')
            ->orderBy('date', 'desc')
            ->take(5)
            ->get();

        $upcomingReservations = Reservation::where('user_id', $user->id)
            ->where('date', '>=', now()->toDateString())
            ->with('service')
            ->orderBy('date', 'asc')
            ->get();

        $pastOrders = Order::where('user_id', $user->id)
            ->with('product')
            ->orderBy('ordered_at', 'desc')
            ->take(5)
            ->get();

        return Inertia::render('Mypage/Index', [
            'user' => $user,
            'pastReservations' => $pastReservations,
            'pastOrders' => $pastOrders,
            'upcomingReservations' => $upcomingReservations,
        ]);
    }
}
