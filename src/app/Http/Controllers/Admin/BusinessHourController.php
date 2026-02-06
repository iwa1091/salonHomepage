<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BusinessHour;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class BusinessHourController extends Controller
{
    /**
     * 管理画面（Inertia）表示用
     * URL: /admin/business-hours
     */
    public function index()
    {
        return Inertia::render('Admin/BusinessHours');
    }

    /**
     * ================================
     * 🔹 [旧API] 曜日単位の営業時間取得
     * ================================
     * （既存構成との互換性維持用）
     *
     * ✅ 現行テーブル（year/month/week_of_month/day_of_week）と矛盾しないように：
     * - 指定がなければ「今月」
     * - week_of_month=1 を代表として返す
     * - open/close は "HH:MM" で返す（input[type=time] 対応）
     */
    public function getHours(Request $request)
    {
        $tz = config('app.timezone', 'Asia/Tokyo');
        $year = (int) $request->input('year', Carbon::now($tz)->year);
        $month = (int) $request->input('month', Carbon::now($tz)->month);

        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            return response()->json(['message' => 'year/month が不正です'], 422);
        }

        if (BusinessHour::where('year', $year)->where('month', $month)->count() === 0) {
            BusinessHour::seedDefaultForMonth($year, $month);
        }

        $hours = BusinessHour::where('year', $year)
            ->where('month', $month)
            ->where('week_of_month', 1)
            ->orderByRaw("FIELD(day_of_week, '月', '火', '水', '木', '金', '土', '日')")
            ->get();

        if ($hours->isEmpty()) {
            $hours = collect(BusinessHour::defaultHours())->map(function ($h) use ($year, $month) {
                return (object) array_merge([
                    'year' => $year,
                    'month' => $month,
                    'week_of_month' => 1,
                ], $h);
            });
        }

        $payload = $hours->map(function ($h) {
            return [
                'year' => (int) ($h->year ?? 0),
                'month' => (int) ($h->month ?? 0),
                'week_of_month' => (int) ($h->week_of_month ?? 1),
                'day_of_week' => $h->day_of_week ?? null,
                'open_time' => BusinessHour::normalizeTimeToHi($h->open_time ?? null),
                'close_time' => BusinessHour::normalizeTimeToHi($h->close_time ?? null),
                'is_closed' => (bool) ($h->is_closed ?? false),
            ];
        });

        return response()->json($payload);
    }

    /**
     * ================================
     * 🔹 [旧API] 曜日単位の営業時間更新
     * ================================
     *
     * ✅ 現行テーブルと矛盾しないように：
     * - year/month 指定が無ければ「今月」
     * - 受け取った曜日設定を「第1〜第5週」全てに反映（旧UIの期待に近い）
     */
    public function updateHours(Request $request)
    {
        $tz = config('app.timezone', 'Asia/Tokyo');
        $year = (int) $request->input('year', Carbon::now($tz)->year);
        $month = (int) $request->input('month', Carbon::now($tz)->month);

        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            return response()->json(['message' => 'year/month が不正です'], 422);
        }

        $hours = $request->all();
        if (!is_array($hours)) {
            return response()->json(['message' => '不正なリクエスト形式です'], 422);
        }

        if (BusinessHour::where('year', $year)->where('month', $month)->count() === 0) {
            BusinessHour::seedDefaultForMonth($year, $month);
        }

        foreach ($hours as $hour) {
            $v = Validator::make($hour, [
                'day_of_week' => ['required', 'string', 'in:月,火,水,木,金,土,日'],
                'open_time' => ['nullable', 'date_format:H:i'],
                'close_time' => ['nullable', 'date_format:H:i'],
                'is_closed' => ['nullable', 'boolean'],
            ])->validate();

            $isClosed = (bool) ($v['is_closed'] ?? false);
            $open_time = $isClosed ? null : ($v['open_time'] ?? null);
            $close_time = $isClosed ? null : ($v['close_time'] ?? null);

            // 第1〜第5週へ反映
            for ($week = 1; $week <= 5; $week++) {
                BusinessHour::updateOrCreate(
                    [
                        'year' => $year,
                        'month' => $month,
                        'week_of_month' => $week,
                        'day_of_week' => $v['day_of_week'],
                    ],
                    [
                        'open_time' => $open_time,
                        'close_time' => $close_time,
                        'is_closed' => $isClosed,
                    ]
                );
            }
        }

        return response()->json(['message' => '営業時間を更新しました'], 200);
    }

    // =======================================================================
    // 🆕 以下が「月 × 週 × 曜日」対応の新API群
    // =======================================================================

    /**
     * 指定した年月の週単位データを取得
     * GET /api/business-hours/weekly?year=2025&month=10
     */
    public function getWeekly(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        $year = (int) $validated['year'];
        $month = (int) $validated['month'];

        // データが存在しない場合は自動で生成（第1〜第5週）
        if (BusinessHour::where('year', $year)->where('month', $month)->count() === 0) {
            BusinessHour::seedDefaultForMonth($year, $month);
        }

        $hours = BusinessHour::where('year', $year)
            ->where('month', $month)
            ->orderBy('week_of_month')
            ->orderByRaw("FIELD(day_of_week, '月','火','水','木','金','土','日')")
            ->get();

        // ✅ UI用に "HH:MM" に正規化して返す
        $payload = $hours->map(function ($h) {
            return [
                'id' => $h->id,
                'year' => (int) $h->year,
                'month' => (int) $h->month,
                'week_of_month' => (int) $h->week_of_month,
                'day_of_week' => $h->day_of_week,
                'open_time' => BusinessHour::normalizeTimeToHi($h->open_time),
                'close_time' => BusinessHour::normalizeTimeToHi($h->close_time),
                'is_closed' => (bool) $h->is_closed,
                'created_at' => $h->created_at,
                'updated_at' => $h->updated_at,
            ];
        });

        return response()->json($payload);
    }

    /**
     * 週単位データを更新
     * PUT /api/business-hours/weekly
     */
    public function updateWeekly(Request $request)
    {
        $records = $request->all();

        if (!is_array($records)) {
            return response()->json(['message' => '不正なリクエスト形式です'], 422);
        }

        foreach ($records as $data) {
            $v = Validator::make($data, [
                'year' => ['required', 'integer', 'between:2000,2100'],
                'month' => ['required', 'integer', 'between:1,12'],
                'week_of_month' => ['required', 'integer', 'between:1,5'],
                'day_of_week' => ['required', 'string', 'in:月,火,水,木,金,土,日'],
                'open_time' => ['nullable', 'date_format:H:i'],
                'close_time' => ['nullable', 'date_format:H:i'],
                'is_closed' => ['nullable', 'boolean'],
            ])->validate();

            $isClosed = (bool) ($v['is_closed'] ?? false);

            // 休業日なら time は null に寄せる（DB/フロント不一致防止）
            $open_time = $isClosed ? null : ($v['open_time'] ?? null);
            $close_time = $isClosed ? null : ($v['close_time'] ?? null);

            // 休業日でない場合は open/close を必須にする（事故防止）
            if (!$isClosed && (!$open_time || !$close_time)) {
                return response()->json([
                    'message' => '休業日でない場合は open_time / close_time を指定してください。',
                ], 422);
            }

            BusinessHour::updateOrCreate(
                [
                    'year' => $v['year'],
                    'month' => $v['month'],
                    'week_of_month' => $v['week_of_month'],
                    'day_of_week' => $v['day_of_week'],
                ],
                [
                    'open_time' => $open_time,
                    'close_time' => $close_time,
                    'is_closed' => $isClosed,
                ]
            );
        }

        return response()->json(['message' => '週単位の営業時間を更新しました'], 200);
    }
}
