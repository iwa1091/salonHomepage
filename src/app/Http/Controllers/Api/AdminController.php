<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
// use App\Models\Schedule; // ✅ Schedule は無効化（BusinessHour 運用へ統一）
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * 管理者向けのサービスおよびスケジュール設定APIを管理するコントローラー
 */
class AdminController extends Controller
{
    // --- サービス (Service) 管理 ---

    /**
     * 全サービスを取得
     */
    public function indexServices()
    {
        // 表示順とアクティブな状態を考慮してサービスを取得
        $services = Service::orderBy('sort_order')->get();
        return response()->json($services);
    }

    /**
     * 新しいサービスを登録
     */
    public function storeService(Request $request)
    {
        // nameはユニークである必要があります
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:services,name',
            'duration_minutes' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $service = Service::create($request->all());
        return response()->json($service, 201);
    }

    /**
     * 既存のサービスを更新
     */
    public function updateService(Request $request, Service $service)
    {
        $validator = Validator::make($request->all(), [
            // nameのユニークチェック時、自分自身は除外する
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('services')->ignore($service->id)],
            'duration_minutes' => 'sometimes|required|integer|min:1',
            'price' => 'sometimes|required|numeric|min:0',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $service->update($request->all());
        return response()->json($service);
    }

    /**
     * サービスを削除
     */
    public function destroyService(Service $service)
    {
        // 実際には、関連する予約の有無を確認するロジックが必要です
        $service->delete();
        return response()->json(['message' => 'サービスが削除されました。'], 200);
    }

    // --- スケジュール (Schedule) 管理 ---

    /**
     * 全スケジュール設定を取得
     */
    public function indexSchedules()
    {
        // ✅ Schedule は利用しない方針（BusinessHour に統一）
        return response()->json([
            'message' => 'Schedule は現在無効です。営業時間管理は BusinessHour を使用してください。',
        ], 410);
    }

    /**
     * 新しいスケジュール設定を登録
     */
    public function storeSchedule(Request $request)
    {
        // ✅ Schedule は利用しない方針（BusinessHour に統一）
        return response()->json([
            'message' => 'Schedule は現在無効です。営業時間管理は BusinessHour を使用してください。',
        ], 410);
    }

    /**
     * 既存のスケジュール設定を更新
     */
    public function updateSchedule(Request $request, $schedule)
    {
        // ✅ Schedule は利用しない方針（BusinessHour に統一）
        return response()->json([
            'message' => 'Schedule は現在無効です。営業時間管理は BusinessHour を使用してください。',
        ], 410);
    }

    /**
     * スケジュール設定を削除
     */
    public function destroySchedule($schedule)
    {
        // ✅ Schedule は利用しない方針（BusinessHour に統一）
        return response()->json([
            'message' => 'Schedule は現在無効です。営業時間管理は BusinessHour を使用してください。',
        ], 410);
    }
}
