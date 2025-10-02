<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            
            // サービス名（例：ラッシュリフト、眉毛ワックス）
            $table->string('name')->unique();
            
            // サービスの説明
            $table->text('description')->nullable();
            
            // 所要時間（分単位）
            // 例: 60, 90, 120 など。予約枠の計算に不可欠なデータです。
            $table->unsignedSmallInteger('duration_minutes');
            
            // 価格
            $table->decimal('price', 8, 0)->unsigned(); // 8桁の整数、小数点以下0桁（円単位）
            
            // 表示順序
            $table->unsignedSmallInteger('sort_order')->default(0);

            // サービスが有効かどうか
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};