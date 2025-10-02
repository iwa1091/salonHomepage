<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ReservationController; // ReservationControllerをインポート

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// 認証済みユーザー情報取得ルート (通常どおり維持)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// --- 管理者向け API ルート ---
// 認証済み (auth:sanctum) かつ管理者権限 (admin: 仮) を持つユーザーのみアクセス可能
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    
    // サービス (Service) 管理ルート
    Route::get('services', [AdminController::class, 'indexServices']);     // 一覧取得
    Route::post('services', [AdminController::class, 'storeService']);    // 新規登録
    Route::put('services/{service}', [AdminController::class, 'updateService']); // 更新
    Route::delete('services/{service}', [AdminController::class, 'destroyService']); // 削除

    // スケジュール (Schedule) 管理ルート
    Route::get('schedules', [AdminController::class, 'indexSchedules']);      // 一覧取得
    Route::post('schedules', [AdminController::class, 'storeSchedule']);     // 新規登録
    Route::put('schedules/{schedule}', [AdminController::class, 'updateSchedule']); // 更新
    Route::delete('schedules/{schedule}', [AdminController::class, 'destroySchedule']); // 削除
});

// --- 一般ユーザー向け API ルート ---
Route::prefix('reservations')->group(function () {
    // 空き時間チェック: GET /api/reservations/availability?date=...&service_id=...
    Route::get('availability', [ReservationController::class, 'checkAvailability']); 
    
    // 予約作成: POST /api/reservations
    Route::post('/', [ReservationController::class, 'store']); 
});
