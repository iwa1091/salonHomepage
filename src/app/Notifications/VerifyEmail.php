<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class VerifyEmail extends BaseVerifyEmail
{
    /**
     * メール送信内容（Markdownメールを構築）
     */
    protected function buildMailMessage($url)
    {
        return (new MailMessage)
            ->subject('【Lash Brow Ohana】メールアドレスの確認をお願いいたします')
            ->greeting('こんにちは！')
            ->line('このメールは、Lash Brow Ohana への会員登録を完了していただくためのものです。')
            ->line('以下のボタンをクリックして、メールアドレスの確認を完了してください。')
            ->action('メールアドレスを確認する', $url)
            ->line('このメールに心当たりがない場合は、破棄してください。')
            ->line('この確認リンクの有効期限は **60分** です。')
            ->salutation("Lash Brow Ohana 運営チーム より");
    }
}
