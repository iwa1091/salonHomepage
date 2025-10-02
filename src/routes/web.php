<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

// ★ 修正点1: App\Http\Middleware\Authenticate をインポートする
use App\Http\Middleware\Authenticate;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;

// 管理者用コントローラー
use App\Http\Controllers\Admin\AdminReservationController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController; 
use App\Http\Controllers\Admin\UserController as AdminUserController; 
use App\Http\Controllers\Admin\ServiceController; // ★ 追加
use App\Http\Controllers\Admin\ScheduleController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ======================
// 管理者専用の認証ルート (分離)
// ======================
Route::prefix('admin')->name('admin.')->group(function () {
    // 管理者ログインフォーム表示 (GET /admin/login)
    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('login');
    // 管理者ログイン処理実行 (POST /admin/login)
    Route::post('/login', [AdminLoginController::class, 'login']);
    // 管理者ログアウト
    Route::post('/logout', [AdminLoginController::class, 'logout'])
        ->middleware('auth:admin')
        ->name('logout');

    // ログイン後（auth:adminガード認証済み）のアクセスのみ許可
    Route::middleware([Authenticate::class . ':admin'])->group(function () {
        // ダッシュボード
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // 予約管理
        Route::get('/reservations', [AdminReservationController::class, 'index'])->name('reservations.index');
        Route::get('/reservations/{id}/edit', [AdminReservationController::class, 'edit'])->name('reservations.edit');
        Route::post('/reservations/{id}/delete', [AdminReservationController::class, 'destroy'])->name('reservations.destroy');

        // ユーザー管理
        Route::get('/users', function () {
            return redirect()->route('admin.dashboard');
        })->name('users.index');

        // 分析 (Analytics)
        Route::get('/analytics', function () {
            return redirect()->route('admin.dashboard');
        })->name('analytics');

        // 設定 (Settings) 💡 スケジュール管理ページへリダイレクト
        Route::get('/settings', function () {
            return redirect()->route('admin.schedule.index');
        })->name('settings');

        // 💡 新規追加: スケジュール管理 (営業時間・例外日の設定)
        Route::prefix('schedule')->name('schedule.')->group(function () {
            Route::get('/', [ScheduleController::class, 'index'])->name('index'); 
            Route::get('/data', [ScheduleController::class, 'getData'])->name('data');
            Route::post('/weekly', [ScheduleController::class, 'storeOrUpdateWeekly'])->name('store.weekly'); 
            Route::post('/exception', [ScheduleController::class, 'storeOrUpdateException'])->name('store.exception');
            Route::delete('/exception', [ScheduleController::class, 'destroyException'])->name('destroy.exception');
        });

        // 商品管理
        Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
        Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
        Route::get('/products/create', [AdminProductController::class, 'create'])->name('products.create');
        Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
        Route::patch('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');

        // ======================
        // サービス管理
        // ======================
        Route::prefix('services')->name('services.')->group(function () {
            Route::get('/', [ServiceController::class, 'index'])->name('index');
            Route::get('/create', [ServiceController::class, 'create'])->name('create');
            Route::post('/', [ServiceController::class, 'store'])->name('store');
            Route::get('/{service}/edit', [ServiceController::class, 'edit'])->name('edit');
            Route::patch('/{service}', [ServiceController::class, 'update'])->name('update');
            Route::delete('/{service}', [ServiceController::class, 'destroy'])->name('destroy');

            // 公開/非公開切替
            Route::patch('/{service}/toggle', [ServiceController::class, 'toggleActive'])->name('toggle');
        });
    });
});

// ======================
// 認証が必要なルート（一般ユーザー）
// ======================
Route::middleware(['auth'])->group(function () {
    Route::get('/email/verify', [EmailVerificationPromptController::class, '__invoke'])
        ->name('verification.notice');

    Route::post('/email/verification-notification', [EmailVerificationPromptController::class, 'store'])
        ->middleware(['throttle:6,1'])
        ->name('verification.send');
    
    Route::get('/email/verify/{id}/{hash}', [Laravel\Fortify\Http\Controllers\VerifyEmailController::class, '__invoke'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    
    Route::get('/home', fn() => redirect()->route('online-store.index'))->name('home');
    Route::get('/dashboard', fn() => redirect()->route('online-store.index'))->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ======================
// Fortify 認証関連ルート（ゲスト用）
// ======================
Route::middleware(['guest'])->group(function () {
    Route::get('/login', [Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [Laravel\Fortify\Http\Controllers\RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [Laravel\Fortify\Http\Controllers\RegisteredUserController::class, 'store']);
    Route::get('/forgot-password', [Laravel\Fortify\Http\Controllers\PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [Laravel\Fortify\Http\Controllers\PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [Laravel\Fortify\Http\Controllers\NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [Laravel\Fortify\Http\Controllers\NewPasswordController::class, 'store'])->name('password.update');
});

// ======================
// 一般ユーザー向けページ
// ======================
Route::get('/', function () {
    return view('home');
})->name('top');

Route::get('/menu_price', [MenuController::class, 'index'])->name('menu_price');
Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery');

// オンラインストア
Route::prefix('online-store')->name('online-store.')->group(function () {
    Route::get('/', [StoreController::class, 'index'])->name('index');
    Route::get('/products/{product}', [StoreController::class, 'show'])->name('show');
    Route::post('/checkout/{product}', [StripeController::class, 'checkout'])->name('checkout');
});

// Stripe Webhook
Route::post('/stripe/webhook', [StripeController::class, 'webhook']);
Route::get('/checkout/success', fn() => view('checkout.success'))->name('checkout.success');
Route::get('/checkout/cancel', fn() => view('checkout.cancel'))->name('checkout.cancel');

// お問い合わせ
Route::get('/contact', [ContactController::class, 'showForm'])->name('contact.form');
Route::post('/contact', [ContactController::class, 'sendEmail'])->name('contact.send');

// 予約機能
Route::get('/reservation', fn() => Inertia::render('Reservation/ReservationForm'))
    ->name('reservation.form');
Route::post('/reservation/store', [ReservationController::class, 'store'])
    ->name('reservation.store');
