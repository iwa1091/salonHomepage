<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'total_reservations',
        'total_purchases',
        'total_spent',
        'last_reservation_at',
        'last_purchase_at',
        'memo',
    ];

    /**
     * 顧客の予約履歴（1対多）
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * 顧客の購入履歴（1対多）
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * 顧客の総利用回数などを更新するメソッド（任意）
     */
    public function recalculateStats(): void
    {
        $this->total_reservations = $this->reservations()->count();
        $this->total_purchases = $this->orders()->count();
        $this->total_spent = $this->orders()->sum('amount');
        $this->last_reservation_at = $this->reservations()->max('date');
        $this->last_purchase_at = $this->orders()->max('ordered_at');
        $this->save();
    }
}
