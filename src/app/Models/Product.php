<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'image_path',
        'stock',
    ];

    protected $casts = [
        'price' => 'integer',
        'stock' => 'integer',
    ];

    /**
     * 画像URLを常に返す（一般的な実装：アクセサ + appends）
     */
    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute(): string
    {
        $fallback = asset('img/logo.jpg');

        $path = $this->image_path;

        // DBに "0" が入っているケースや空値は画像なし扱い
        if (!is_string($path) || $path === '' || $path === '0') {
            return $fallback;
        }

        // 念のため正規化（storage/ が付いていても public disk のパスに寄せる）
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        try {
            // ファイルが存在しないならフォールバック
            if (!Storage::disk('public')->exists($path)) {
                return $fallback;
            }

            // /storage/products/xxx.jpg または環境によっては絶対URLを返すため、asset() で包まない
            return Storage::disk('public')->url($path);
        } catch (\Throwable $e) {
            return $fallback;
        }
    }
}
