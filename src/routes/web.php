<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\RoutePath;

// 🔹 共通ミドルウェア
use App\Http\Middleware\Authenticate;

// 🔹 一般ページ
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\UserReservationController;

// 🔹 Stripe
use App\Http\Controllers\StripeController;
use App\Http\Controllers\StripeWebhookController;

// 🔹 Fortify / 認証
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\RegisteredUserController;

// 🔹 管理者
use App\Http\Controllers\Admin\AdminReservationController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\BusinessHourController;
use App\Http\Controllers\Admin\ScheduleController;

// 🔹 マイページ
use App\Http\Controllers\MypageReservationLinkController;
use App\Http\Controllers\MypageController;



/*
|--------------------------------------------------------------------------
| マイページ（ユーザー専用）
|--------------------------------------------------------------------------
*/
Route::post('/mypage/link-reservation', [MypageReservationLinkController::class, 'link'])
    ->middleware(['auth', 'verified'])
    ->name('mypage.link-reservation');
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/mypage', [MypageController::class, 'index'])->name('mypage.index');
});


/*
|--------------------------------------------------------------------------
| 管理者ルート
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {

    // 認証
    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login']);
    Route::post('/logout', [AdminLoginController::class, 'logout'])->middleware('auth:admin')->name('logout');

    // 管理者専用エリア
    Route::middleware([Authenticate::class . ':admin'])->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // 予約管理
        Route::get('/reservations', [AdminReservationController::class, 'index'])->name('reservations.index');
        Route::get('/reservations/{id}/edit', [AdminReservationController::class, 'edit'])->name('reservations.edit');
        Route::post('/reservations/{id}/delete', [AdminReservationController::class, 'destroy'])->name('reservations.destroy');

        // 営業時間
        Route::get('/business-hours', [BusinessHourController::class, 'index'])->name('business-hours.index');

        // 商品管理
        Route::resource('products', AdminProductController::class);

        // サービス管理
        Route::resource('services', ServiceController::class)->except(['show']);
        Route::patch('services/{service}/toggle', [ServiceController::class, 'toggleActive'])->name('services.toggle');

        // カテゴリ管理
        Route::resource('categories', CategoryController::class)->except(['show']);

        // スケジュール
        Route::prefix('schedule')->name('schedule.')->group(function () {
            Route::get('/', [ScheduleController::class, 'index'])->name('index');
            Route::get('/data', [ScheduleController::class, 'getData'])->name('data');
            Route::post('/weekly', [ScheduleController::class, 'storeOrUpdateWeekly'])->name('store.weekly');
            Route::post('/exception', [ScheduleController::class, 'storeOrUpdateException'])->name('store.exception');
            Route::delete('/exception', [ScheduleController::class, 'destroyException'])->name('destroy.exception');
        });

        // 顧客一覧
        Route::get('/users', [CustomerController::class, 'index'])->name('users.index');
    });
});


/*
|--------------------------------------------------------------------------
| ユーザー認証が必要なページ
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // メール認証
    Route::get('/email/verify', [EmailVerificationPromptController::class, '__invoke'])->name('verification.notice');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware(['throttle:6,1'])->name('verification.send');
    Route::get('/email/verify/{id}/{hash}', [Laravel\Fortify\Http\Controllers\VerifyEmailController::class, '__invoke'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    // プロフィール
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


/*
|--------------------------------------------------------------------------
| Fortify（ゲスト）
|--------------------------------------------------------------------------
*/
Route::middleware(['guest'])->group(function () {

    Route::get(RoutePath::for('login', '/login'), [Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post(RoutePath::for('login', '/login'), [Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'store']);

    // 会員登録
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});


/*
|--------------------------------------------------------------------------
| 一般ページ
|--------------------------------------------------------------------------
*/
Route::get('/', fn() => view('home'))->name('top');
Route::get('/menu_price', [ServiceController::class, 'publicIndex'])->name('menu_price');
Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery');


/*
|--------------------------------------------------------------------------
| オンラインストア
|--------------------------------------------------------------------------
*/
Route::prefix('online-store')->name('online-store.')->group(function () {
    Route::get('/', [StoreController::class, 'index'])->name('index');
    Route::get('/products/{product}', [StoreController::class, 'show'])->name('show');
    Route::post('/checkout/{product}', [StripeController::class, 'checkout'])->name('checkout');
});


/*
|--------------------------------------------------------------------------
| Stripe Webhook
|--------------------------------------------------------------------------
*/
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
    ->withoutMiddleware(['web', 'auth', Authenticate::class])
    ->name('stripe.webhook');


/*
|--------------------------------------------------------------------------
| Stripe 戻り
|--------------------------------------------------------------------------
*/
Route::get('/checkout/success', [StoreController::class, 'success'])->name('checkout.success');
Route::get('/checkout/cancel', [StoreController::class, 'cancel'])->name('checkout.cancel');


/*
|--------------------------------------------------------------------------
| お問い合わせ
|--------------------------------------------------------------------------
*/
Route::get('/contact', [ContactController::class, 'showForm'])->name('contact.form');
Route::post('/contact', [ContactController::class, 'sendEmail'])->name('contact.send');


/*
|--------------------------------------------------------------------------
| 予約ページ
|--------------------------------------------------------------------------
*/
Route::get('/reservation', [ReservationController::class, 'form'])->name('reservation.form');
Route::post('/reservation/store', [ReservationController::class, 'store'])->name('reservation.store');
