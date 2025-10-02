<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactMail;

class ContactController extends Controller
{
    /**
     * お問い合わせフォームの表示
     *
     * @return \Illuminate\View\View
     */
    public function showForm()
    {
        return view('contact');
    }

    /**
     * お問い合わせフォームの送信
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendEmail(Request $request)
    {
        // フォーム入力の検証
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        try {
            // メール送信
            Mail::to(config('mail.to.address')) // 管理者宛て
                ->send(new ContactMail($validatedData));

            // 成功メッセージをセッションに保存
            return redirect()->route('contact.form')->with('success', 'お問い合わせありがとうございます。2営業日以内に返信いたします。');

        } catch (\Exception $e) {
            // エラーログ
            Log::error('メール送信エラー: ' . $e->getMessage());

            // 失敗メッセージをセッションに保存
            return redirect()->back()->with('error', 'メール送信中にエラーが発生しました。時間をおいて再度お試しください。')->withInput();
        }
    }
}
