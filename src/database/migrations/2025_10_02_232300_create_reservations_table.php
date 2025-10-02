<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            
            // ユーザー関連 (認証済みユーザーの場合)
            // 外部キー制約を付け、ユーザーが削除されたら予約も連鎖削除
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            
            // サービス関連 (必須: どのサービスが予約されたか)
            // ★事前に 'services' テーブルが必要
            $table->foreignId('service_id')->constrained()->onDelete('cascade');

            // 予約者情報 (ゲスト予約も想定し残す)
            $table->string('name');
            $table->string('email');
            
            // 時間情報
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time'); // サービス時間に基づいて計算される

            // 予約ステータスとメモ
            $table->string('status')->default('pending')->comment('pending, confirmed, cancelled, completed');
            $table->text('notes')->nullable();
            
            $table->timestamps();

            // ★重複予約防止のための複合ユニーク制約 (ユーザーは同じ時間に予約できない)
            // $table->unique(['user_id', 'date', 'start_time']); 
            // ただし、管理者向け予約システムでは、より複雑なロジックが必要なため、
            // 予約ロジック（コントローラー）側で重複チェックを行う方が一般的です。
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};