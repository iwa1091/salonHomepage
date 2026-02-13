<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Service extends Model
{
    use HasFactory;

    protected $appends = ['image_url'];

    /**
     * 一括割り当て可能な属性
     */
    protected $fillable = [
        'name',
        'description',
        'duration_minutes',
        'price',
        'sort_order',
        'is_active',
        'image',       // サービス画像
        'features',    // サービス特徴（JSON配列）
        'category_id', // 外部キーとしてカテゴリID
        'is_popular'   // 人気フラグ
    ];

    /**
     * 型キャスト
     */
    protected $casts = [
        'price' => 'decimal:0',
        'duration_minutes' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'features' => 'array',   // JSON ⇔ 配列 に自動変換
        'is_popular' => 'boolean',
    ];

    /**
     * リレーション: サービスに紐づく予約
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(\App\Models\Reservation::class);
    }

    /**
     * リレーション: サービスが属するカテゴリ
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Category::class);
    }

    public function getImageUrlAttribute(): string
    {
        $fallback = asset('img/logo.jpg');

        $path = $this->image;

        if (!is_string($path) || $path === '' || $path === '0') {
            return $fallback;
        }

        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        try {
            if (!Storage::disk('public')->exists($path)) {
                return $fallback;
            }
            return Storage::disk('public')->url($path);
        } catch (\Throwable $e) {
            return $fallback;
        }
    }
}
