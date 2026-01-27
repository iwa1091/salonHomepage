<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\Reservation;

class TimetableController extends Controller
{
    /**
     * Timetableページ（Inertia）
     * URL: GET /admin/timetable?date=YYYY-MM-DD
     * Route name: admin.timetable.index
     */
    public function index(Request $request)
    {
        $tz = config('app.timezone', 'Asia/Tokyo');

        $date = $request->query('date');
        if (!$date) {
            $date = Carbon::now($tz)->toDateString();
        }

        // 初期表示用に当日の予約を渡す（最小・確実）
        $reservations = Reservation::query()
            ->whereDate('date', $date)
            ->orderBy('start_time')
            ->get([
                'id',
                'date',
                'start_time',
                'name',
                'email',
                'phone',
                'service_id',
            ]);

        return Inertia::render('Admin/Timetable', [
            'date' => $date,
            'initialReservations' => $reservations,
        ]);
    }

    /**
     * Timetable用 JSON API（最小）
     * URL: GET /admin/api/timetable?date=YYYY-MM-DD
     */
    public function getData(Request $request)
    {
        $request->validate([
            'date' => ['required', 'date'],
        ]);

        $date = $request->query('date');

        $reservations = Reservation::query()
            ->whereDate('date', $date)
            ->orderBy('start_time')
            ->get([
                'id',
                'date',
                'start_time',
                'name',
                'email',
                'phone',
                'service_id',
            ]);

        return response()->json([
            'date' => $date,
            'reservations' => $reservations,
        ]);
    }
}
