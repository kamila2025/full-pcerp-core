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
        Schema::create('adjustment_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('人員');
            $table->unsignedBigInteger('location_id')->comment('地點');
            $table->string('order_number')->comment('盤點單號碼');
            $table->dateTime('start_at')->nullable()->comment('盤點開始時間');
            $table->dateTime('end_at')->nullable()->comment('盤點結束時間');
            $table->longText('remark')->nullable()->comment('備註');
            $table->dateTime('corrected_at')->nullable()->comment('校正時間');
            $table->string('result')->comment('盤點結果');
            $table->string('status')->comment('盤點狀態');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adjustment_orders');
    }
};
