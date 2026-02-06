<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_blocks', function (Blueprint $table) {
            $table->id();

            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');

            // ✅ 固定レーン（予約は lane=1、ブロックは lane=2/3 想定）
            $table->unsignedTinyInteger('lane')->default(2); // 2 or 3

            // ✅ lash-brow-ohana（ReservationForm.jsx）に寄せた表示/メモ情報
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();

            // ✅ どのメニュー相当のブロックか（任意）
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['date', 'lane']);
            $table->index(['date', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_blocks');
    }
};
