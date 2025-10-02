<?php

namespace App\Models;

use App\Notifications\VerifyEmail as VerifyEmailNotification;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailNotification);
    }

    /**
     * 管理者であるかどうかをチェックするメソッドを追加
     * roleカラムが 'admin' であるかを基準とする
     * データベースの値に合わせて 'admin' の部分を調整してください。
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        // データベースの 'role' カラムの値が 'admin' と一致するかどうかで判定
        // もし 'role' カラムではなく 'is_admin' のような boolean カラムを使っている場合は、
        // return (bool) $this->is_admin; のように変更してください。
        return $this->role === 'admin';
    }


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
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
