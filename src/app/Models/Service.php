<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    /**
     * 一括割り当て可能な属性 (Mass Assignable)。
     * マイグレーションで定義した全カラムを含めます。
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'duration_minutes',
        'price',
        'sort_order',
        'is_active',
    ];

    /**
     * モデルの属性のキャスト設定。
     * 価格は数値、アクティブフラグはブール値にキャストします。
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:0', // 小数点以下0桁でキャスト
        'duration_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * このサービスを持つ複数の予約 (Reservation) を取得します。
     * 一対多のリレーションシップを定義します。
     */
    public function reservations(): HasMany
    {
        // ReservationモデルがApp\Models\Reservationにあると仮定
        return $this->hasMany(Reservation::class); 
    }
}
