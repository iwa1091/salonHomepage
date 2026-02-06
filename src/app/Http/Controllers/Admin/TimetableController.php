<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\Reservation;
use App\Models\BusinessHour;
use App\Models\AdminBlock;

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
        // ※ Timetable.jsx は基本 /admin/api/timetable を叩きますが、
        //    初期表示の保険として end_time / notes / service_name も返しておく
        $reservations = Reservation::query()
            ->with('service')
            ->whereDate('date', $date)
            ->orderBy('start_time')
            ->get([
                'id',
                'date',
                'start_time',
                'end_time',
                'name',
                'email',
                'phone',
                'notes',
                'service_id',
            ])
            ->map(function ($r) {
                return [
                    'id'           => $r->id,
                    'date'         => $r->date,
                    'start_time'   => $r->start_time,
                    'end_time'     => $r->end_time,
                    'name'         => $r->name,
                    'email'        => $r->email,
                    'phone'        => $r->phone,
                    'notes'        => $r->notes,
                    'service_id'   => $r->service_id,
                    'service_name' => $r->service?->name,
                    'duration_minutes' => $r->service?->duration_minutes,
                ];
            });

        return Inertia::render('Admin/Timetable', [
            'date' => $date,
            'initialReservations' => $reservations,
        ]);
    }

    /**
     * Timetable用 JSON API
     * URL: GET /admin/api/timetable?date=YYYY-MM-DD
     *
     * Timetable.jsx が期待する形：
     * - business_hour（open/close/is_closed）
     * - reservations（start/end + 表示用項目）
     * - blocks（管理者ブロック）
     */
    public function getData(Request $request)
    {
        $request->validate([
            'date' => ['required', 'date'],
        ]);

        $tz = config('app.timezone', 'Asia/Tokyo');
        $date = $request->query('date');

        // ----------------------------
        // business_hour（当日分）
        // BusinessHour の構造が「year/month/week_of_month/day_of_week(日〜土)」前提
        // ----------------------------
        $target = Carbon::parse($date, $tz);

        $dayNamesJp = ['日', '月', '火', '水', '木', '金', '土'];
        $dayOfWeekJp = $dayNamesJp[$target->dayOfWeek]; // Carbon: Sun=0..Sat=6

        // PHP側 BusinessHour::getWeekOfMonth と合わせる想定：
        // ceil((day + firstDay->dayOfWeekIso - 1)/7)
        $firstDayIso = $target->copy()->startOfMonth()->dayOfWeekIso; // Mon=1..Sun=7
        $weekOfMonth = (int) ceil(($target->day + $firstDayIso - 1) / 7);

        $bh = BusinessHour::query()
            ->where('year', $target->year)
            ->where('month', $target->month)
            ->where('week_of_month', $weekOfMonth)
            ->where('day_of_week', $dayOfWeekJp)
            ->first();

        // レコードが無い場合は「休業扱い」にしておく（表示の誤解防止）
        $businessHour = [
            'is_closed'  => $bh ? (bool) $bh->is_closed : true,
            'open_time'  => $bh && $bh->open_time ? substr((string) $bh->open_time, 0, 5) : '09:00',
            'close_time' => $bh && $bh->close_time ? substr((string) $bh->close_time, 0, 5) : '19:30',
        ];

        // ----------------------------
        // reservations（当日分）
        // ----------------------------
        $reservations = Reservation::query()
            ->with('service')
            ->whereDate('date', $date)
            ->orderBy('start_time')
            ->get([
                'id',
                'date',
                'start_time',
                'end_time',
                'name',
                'email',
                'phone',
                'notes',
                'service_id',
                'status',
            ])
            ->map(function ($r) {
                return [
                    'id'           => $r->id,
                    'date'         => $r->date,
                    'start_time'   => $r->start_time, // "HH:MM:SS"でも Timetable.jsx 側で抽出OK
                    'end_time'     => $r->end_time,
                    'name'         => $r->name,
                    'email'        => $r->email,
                    'phone'        => $r->phone,
                    'notes'        => $r->notes,
                    'status'       => $r->status,
                    'service_id'   => $r->service_id,
                    'service_name' => $r->service?->name,
                    'duration_minutes' => $r->service?->duration_minutes,
                ];
            })
            ->values();

        // ----------------------------
        // blocks（当日分）
        // ----------------------------
        $blocks = AdminBlock::query()
            ->with('service')
            ->whereDate('date', $date)
            ->orderBy('lane')
            ->orderBy('start_time')
            ->get()
            ->map(function ($b) {
                return [
                    'id'         => $b->id,
                    'date'       => $b->date ? $b->date->toDateString() : null,
                    'lane'       => (int) $b->lane,
                    'start_time' => $b->start_time,
                    'end_time'   => $b->end_time,

                    // ✅ lash-brow-ohana（ReservationForm.jsx）に寄せた表示用項目
                    'name'       => $b->name ?? null,
                    'email'      => $b->email ?? null,
                    'phone'      => $b->phone ?? null,
                    'notes'      => $b->notes ?? null,

                    'service_id'   => $b->service_id ?? null,
                    'service_name' => $b->service?->name,
                    'duration_minutes' => $b->service?->duration_minutes,
                ];
            })
            ->values();

        return response()->json([
            'date' => $date,
            'business_hour' => $businessHour,
            'reservations' => $reservations,
            'blocks' => $blocks,
        ]);
    }
}
