<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    /**
     * 一括割り当て可能な属性 (Mass Assignable)
     * マイグレーションで追加したカラムを含めます。
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',      // 外部キー
        'service_id',   // 外部キー
        'name',
        'email',
        'date',
        'start_time',   // 追加
        'end_time',     // 追加
        'status',       // 追加
        'notes',        // 追加
    ];

    /**
     * 予約とUserモデル（顧客）の関係を定義します。
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 予約とServiceモデル（サービス）の関係を定義します。
     * ※ 'Service' モデルも作成する必要があります。
     */
    public function service()
    {
        // ServiceモデルがApp\Models\Serviceにあると仮定
        return $this->belongsTo(Service::class); 
    }
    
    // 適切なデータ型のキャストを追加することも推奨されます
    protected $casts = [
        'date' => 'date',
        // 'start_time' と 'end_time' はDBからCarbonインスタンスとして取得したい場合、
        // 'datetime' を使用することが多いですが、ここでは時間だけなので一旦省略します。
    ];
}