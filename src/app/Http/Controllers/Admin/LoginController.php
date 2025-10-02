<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginController extends Controller
{
    // ログイン/ログアウト処理にAuthenticatesUsersトレイトを使用
    use AuthenticatesUsers;

    /**
     * ログイン後にリダイレクトさせる場所を定義します。
     * adminガード専用なので、直接パスを指定します。
     *
     * @var string
     */
    protected $redirectTo = '/admin/dashboard';

    /**
     * コンストラクタ
     * adminガードを使用するように設定し、認証されていないユーザーのみアクセスを許可します。
     */
    public function __construct()
    {
        // 管理者ガード(admin)を使用し、未認証(guest)の場合のみアクセスを許可
        $this->middleware('guest:admin')->except('logout');
    }

    /**
     * 管理者ログインフォームを表示
     */
    public function showLoginForm()
    {
        // resources/views/admin/login.blade.php を表示
        return view('admin/login'); // フォルダ構造に合わせてスラッシュに変更
    }

    /**
     * ログアウト時に使用するガードをadminに指定
     *
     * @return string
     */
    protected function guard()
    {
        // adminガードを使用して認証を行う
        return Auth::guard('admin');
    }

    /**
     * ログアウト後のリダイレクト先
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function loggedOut(Request $request)
    {
        return route('admin.login');
    }

    /**
     * ユーザーモデルに管理者フラグがある場合、認証時にチェックする
     * 一般ユーザーと管理者のログイン処理を分離するため、このメソッドをオーバーライドします。
     * * 【修正点】管理者としてログインを試みる際、クレデンシャルに 'role' => 'admin' を含め、
     * パスワード検証と管理者ロールのチェックを同時に行います。
     * これにより、AdminMiddlewareのチェックロジックと整合します。
     */
    protected function attemptLogin(Request $request)
    {
        // ユーザーから入力されたメールアドレスとパスワードを取得
        $credentials = $this->credentials($request);
        
        // 管理者としてログインさせるため、クレデンシャルに 'role' => 'admin' を追加
        // Auth::guard('admin')->attempt() は、この追加された条件も同時にチェックします。
        $credentials = array_merge($credentials, ['role' => 'admin']);

        // adminガードでログインを試みる
        return $this->guard()->attempt(
            $credentials, $request->filled('remember')
        );
        
        // 元のコードで意図されていた isAdmin() チェックは不要になります。
        // なぜなら、adminガードが 'role' => 'admin' の条件でデータベースからレコードを探すためです。
    }

    // 元のコードにあった isAdmin() チェックのロジックは削除します。
    // protected function attemptLogin(Request $request) { ... }
    // ...
    // この修正により、ログイン時に 'role' が 'admin' のユーザーのみが
    // adminガードで認証されるようになり、AdminMiddlewareのチェックが成功するようになります。
}