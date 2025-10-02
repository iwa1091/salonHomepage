<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Lang;

class VerifyEmail extends Notification
{
    use Queueable;

    /**
     * Get the notification's channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        // 修正したverificationUrlメソッドを呼び出します
        $verificationUrl = $this->verificationUrl($notifiable);
        $seconds = config('auth.verification.expire', 60);
        $minutes = $seconds / 60;

        return (new MailMessage)
            ->subject(Lang::get('メールアドレスの確認'))
            ->line(Lang::get('このメールは、当サービスへのご登録を完了していただくためのものです。'))
            ->line(Lang::get('下記ボタンをクリックして、メールアドレスの確認を完了してください。'))
            ->action(Lang::get('メールアドレスを確認'), $verificationUrl)
            ->line(Lang::get('このメールに心当たりのない場合は、本メールを破棄してください。'))
            ->line(Lang::get('このメールの有効期限は :minutes分です。', ['minutes' => $minutes]));
    }

    /**
     * Get the verification URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function verificationUrl($notifiable)
    {
        // 認証完了後にリダイレクトしたいURLをここに設定します
        // online-store.index ルートを使用することで、productパラメータが不要になります。
        $redirectUrl = route('online-store.index');

        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
                // カスタムリダイレクトURLをクエリパラメータとして追加します
                'redirect' => $redirectUrl,
            ]
        );
    }
}
