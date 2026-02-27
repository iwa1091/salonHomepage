<?php  

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;

// 共通ミドルウェア
use App\Http\Middleware\Authenticate;

// 一般ページ
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;

// Stripe
use App\Http\Controllers\StripeController;
use App\Http\Controllers\StripeWebhookController;

// 認証（React 用）
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\VerifyEmailController;  // Fortify標準ではなくカスタムVerifyEmailControllerを使用

// 管理者
use App\Http\Controllers\Admin\AdminReservationController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\BusinessHourController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\TimetableController;
use App\Http\Controllers\Admin\AdminBlockController;

// マイページ
use App\Http\Controllers\MypageReservationLinkController;
use App\Http\Controllers\MypageController;

// 予約履歴 / キャンセル（ログインユーザー）
use App\Http\Controllers\UserReservationController;

// メールからのキャンセル（署名付きURLで保護：Controller未作成でも動くよう routes に最小実装）
use App\Models\Reservation;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\AdminReservationCanceledMail;
use App\Mail\UserReservationCanceledMail;

/*
|--------------------------------------------------------------------------
| マイページ（認証 + メール認証済）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/mypage/link-reservation', [MypageReservationLinkController::class, 'link'])
        ->name('mypage.link-reservation');

    Route::get('/mypage', [MypageController::class, 'index'])
        ->name('mypage.index');

    // ✅ 予約履歴一覧（ユーザー向け）
    Route::get('/mypage/reservations', [UserReservationController::class, 'index'])
        ->name('mypage.reservations.index');

    // ✅ マイページ：キャンセル確認（Blade confirm へ）
    Route::get('/mypage/reservations/{reservation}/cancel/confirm', [UserReservationController::class, 'cancelConfirm'])
        ->name('mypage.reservations.cancel.confirm');

    // ✅ マイページからのキャンセル（ログイン必須）
    Route::post('/mypage/reservations/{id}/cancel', [UserReservationController::class, 'cancel'])
        ->name('mypage.reservations.cancel');

    // ✅ マイページからの予約作成（user_id を必ず紐付ける）
    Route::post('/mypage/reservations/store', [UserReservationController::class, 'storeFromMypage'])
        ->name('mypage.reservations.store');
});

/*
|--------------------------------------------------------------------------
| 公開：予約メールからのキャンセル（署名付きURL）
|--------------------------------------------------------------------------
| - ログイン不要
| - 署名(signed)で改ざん防止
| - GET: 確認ページ（Bladeへ切替）
| - POST: キャンセル実行 + ユーザー/管理者へメール送信（Bladeへ切替）
|
| ※ 将来的に Controller + Blade(view) へ切り出す場合も、URL/route名を維持すれば互換性を保てます。
|--------------------------------------------------------------------------
*/
Route::middleware(['signed'])->group(function () {

    // 確認ページ
    Route::get('/reservations/{reservation}/cancel', function (Request $request, Reservation $reservation) {
        $reservation->load('service');

        $title = 'キャンセル確認 | Lash Brow Ohana';
        $isCanceled = ($reservation->status === 'canceled');

        // ✅ signed middleware が POST にも効くため、POST先も「署名付きURL」で生成する
        $action = \Illuminate\Support\Facades\URL::signedRoute(
            'reservations.public.cancel.perform',
            ['reservation' => $reservation->id]
        );

        return view('reservations.cancel.confirm', [   // ✅ 変更：view名
            'title'       => $title,
            'reservation' => $reservation,
            'isCanceled'  => $isCanceled,
            'action'      => $action,
            'home'        => url('/'),
        ]);
    })->name('reservations.public.cancel.show');

    // キャンセル実行
    Route::post('/reservations/{reservation}/cancel', function (Request $request, Reservation $reservation) {
        $reservation->load('service');

        $alreadyCanceled = ($reservation->status === 'canceled');

        if (!$alreadyCanceled) {
            // ✅ cancel_reason を保存（任意）
            $validated = $request->validate([
                'cancel_reason' => ['nullable', 'string', 'max:500'],
            ]);

            $reservation->update([
                'status' => 'canceled',
                'cancel_reason' => $validated['cancel_reason'] ?? null,
            ]);

            try {
                // 管理者へキャンセル通知
                $adminEmail = env('MAIL_ADMIN_ADDRESS', 'admin@lash-brow-ohana.local');
                Mail::to($adminEmail)->send(new AdminReservationCanceledMail($reservation));

                // ユーザーへキャンセル通知
                if (!empty($reservation->email)) {
                    Mail::to($reservation->email)->send(new UserReservationCanceledMail($reservation));
                }
            } catch (\Throwable $e) {
                Log::error('[公開キャンセル通知メール送信エラー]', [
                    'reservation_id' => $reservation->id,
                    'email' => $reservation->email ?? '不明',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $home = url('/');
        $message = $alreadyCanceled ? 'この予約は既にキャンセル済みです。' : '予約をキャンセルしました。';

        return view('reservations.cancel.done', [      // ✅ 変更：view名
            'title'           => 'キャンセル結果 | Lash Brow Ohana',
            'reservation'     => $reservation,
            'alreadyCanceled' => $alreadyCanceled,
            'message'         => $message,
            'home'            => $home,
        ]);
    })->name('reservations.public.cancel.perform');
});


/*
|--------------------------------------------------------------------------
| 管理者ルート
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login']);
    Route::post('/logout', [AdminLoginController::class, 'logout'])
        ->middleware('auth:admin')
        ->name('logout');

    Route::middleware([Authenticate::class . ':admin'])->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // 予約関連
        Route::get('/reservations', [AdminReservationController::class, 'index'])->name('reservations.index');
        Route::get('/reservations/{id}/edit', [AdminReservationController::class, 'edit'])->name('reservations.edit');
        Route::put('/reservations/{id}', [AdminReservationController::class, 'update'])->name('reservations.update');
        Route::post('/reservations/{id}/delete', [AdminReservationController::class, 'destroy'])->name('reservations.destroy');

        // 営業時間設定
        Route::get('/business-hours', [BusinessHourController::class, 'index'])->name('business-hours.index');

        // 商品・メニュー・カテゴリ
        Route::resource('products', AdminProductController::class);
        Route::resource('services', ServiceController::class)->except(['show']);
        Route::patch('services/{service}/toggle', [ServiceController::class, 'toggleActive'])->name('services.toggle');
        Route::resource('categories', CategoryController::class)->except(['show']);

        // スケジュール
        Route::prefix('schedule')->name('schedule.')->group(function () {
            Route::get('/', [ScheduleController::class, 'index'])->name('index');
            Route::get('/data', [ScheduleController::class, 'getData'])->name('data');
            Route::post('/weekly', [ScheduleController::class, 'storeOrUpdateWeekly'])->name('store.weekly');
            Route::post('/exception', [ScheduleController::class, 'storeOrUpdateException'])->name('store.exception');
            Route::delete('/exception', [ScheduleController::class, 'destroyException'])->name('destroy.exception');
        });

        // 顧客一覧（Admin/UserList.jsx と CustomerController@index 用）
        Route::get('/users', [CustomerController::class, 'index'])->name('users.index');

        // 管理者用 JSON API（Session認証下で提供）
        Route::prefix('api')->group(function () {
            Route::get('reservations', [AdminReservationController::class, 'apiIndex']);
            Route::get('timetable', [TimetableController::class, 'getData']);

            // ✅ 予約：ドラッグリサイズ用時間更新API
            Route::put('reservations/{id}', [AdminReservationController::class, 'updateTime']);

            // ✅ 管理者ブロックAPI（Timetable.jsx が /admin/api/blocks を叩く想定）
            Route::post('blocks', [AdminBlockController::class, 'store']);
            Route::put('blocks/{id}', [AdminBlockController::class, 'update']);
            Route::delete('blocks/{id}', [AdminBlockController::class, 'destroy']);
        });

        // Timetable ページ（Inertia）
        Route::get('/timetable', [TimetableController::class, 'index'])
            ->name('timetable.index');
    });
});

/*
|--------------------------------------------------------------------------
| React 認証（ゲストのみ）
|--------------------------------------------------------------------------
*/
Route::middleware(['guest'])->group(function () {

    // Login
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    // Register
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    // Forgot Password（メール送信）
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');

    // Reset Password（パスワード再設定）
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| 認証済（メール認証前でもアクセス可）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // メール認証ページ（React）
    Route::get('/email/verify', [EmailVerificationPromptController::class, '__invoke'])
        ->name('verification.notice');

    // 認証メール再送
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware(['throttle:6,1'])
        ->name('verification.send');

    // メール認証リンク（完了）
    Route::get(
        '/email/verify/{id}/{hash}',
        [VerifyEmailController::class, '__invoke'] // カスタム VerifyEmailController を使用
    )
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // プロフィール
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| 一般ページ
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => view('home'))->name('top');
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
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class, 'auth', Authenticate::class])
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
