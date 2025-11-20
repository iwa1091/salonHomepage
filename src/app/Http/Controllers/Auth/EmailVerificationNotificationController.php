<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * 認証メール再送処理（Blade + 日本語対応 + 二重送信防止）
     */
    public function store(Request $request): RedirectResponse
    {
        // ✅ すでに認証済みならホームへリダイレクト
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('home', absolute: false));
        }

        // ✅ Fortify 標準動作に準拠
        // → User モデル内の sendEmailVerificationNotification() を呼び出す
        // （ここでブランド仕様 VerifyEmail が1通だけ送信される）
        $request->user()->sendEmailVerificationNotification();

        // ✅ Blade 版 verify-email.blade.php でフラッシュメッセージ表示
        //  → `session('resent')` により「新しい認証メールを送信しました。」が表示される
        return back()->with('resent', true);
    }
}
