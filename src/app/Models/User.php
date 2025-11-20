<?php

namespace App\Models;

use App\Notifications\VerifyEmail as VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * 一括代入可能な属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
    ];

    /**
     * メール認証通知を送信（ブランド仕様 + 二重送信防止）
     *
     * Laravel標準の VerifyEmail をブランド版に差し替え。
     * Fortifyの Registered イベントから自動的に呼ばれる。
     */
    public function sendEmailVerificationNotification(): void
    {
        // ✅ すでに認証済みのユーザーには送らない
        if ($this->hasVerifiedEmail()) {
            return;
        }

        // ✅ 通知を1通だけ送信（重複防止）
        $this->notify(new VerifyEmailNotification());
    }

    /**
     * 管理者判定メソッド
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * JSONシリアライズ時に非表示にする属性
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * 属性キャスト設定
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
