<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Reservation extends Model
{
    use HasFactory;

    /**
     * 一括割り当て可能な属性 (Mass Assignable)
     */
    protected $fillable = [
        'customer_id',
        'user_id',          // 顧客 (User)
        'service_id',       // メニュー (Service)
        'name',
        'email',
        'phone',            // ← 追加（必須）
        'date',
        'start_time',
        'end_time',
        'status',
        'notes',
        'cancel_reason',
        'reservation_code', // マイページ紐づけ番号
    ];

    /**
     * 🔹 User（顧客）とのリレーション
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 🔹 Service（メニュー）とのリレーション
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * 🔹 日付・時間系のキャスト設定
     *
     * ※ start_time / end_time は DB が TIME 型のため、datetime キャストすると
     *    「日付付きの Carbon」に変換され、日付文字列連結時に二重日付エラーの原因になります。
     *    ここではキャストせず、文字列（例: "09:00:00"）として扱います。
     */
    protected $casts = [
        'date' => 'date',
    ];

    /**
     * 🔹 アクセサ：表示用フォーマット
     */
    public function getFormattedDateAttribute(): string
    {
        return Carbon::parse($this->date)->format('Y年m月d日');
    }

    public function getFormattedTimeAttribute(): string
    {
        // start_time は TIME 文字列（"H:i" or "H:i:s"）想定
        $time = $this->start_time;

        if ($time instanceof \DateTimeInterface) {
            return Carbon::instance($time)->format('H:i');
        }

        if (is_string($time) && $time !== '') {
            // "09:00:00" を優先して扱う（TIME型）
            $dt = preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)
                ? Carbon::createFromFormat('H:i:s', $time)
                : Carbon::createFromFormat('H:i', $time);

            return $dt->format('H:i');
        }

        return '—';
    }

    /**
     * 🔹 状態ラベル
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'confirmed' => '確定',
            'pending'   => '保留',
            'canceled'  => 'キャンセル',
            default     => '不明',
        };
    }
}
