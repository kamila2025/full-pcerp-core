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
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->cascadeOnDelete()->comment('庫存');
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->comment('商品');
            $table->unsignedBigInteger('variant_id')->comment('規格');
            $table->unsignedBigInteger('location_id')->comment('倉庫');
            $table->string('type')->comment('類型');
            $table->integer('quantity_before')->comment('變更前庫存');
            $table->integer('quantity_after')->comment('變更後庫存');
            $table->integer('quantity_change')->comment('變更庫存');
            $table->string('reason')->nullable()->comment('原因');
            $table->nullableMorphs('causer');       // 操作者
            $table->nullableMorphs('reference');    // 參考
            $table->longText('properties')->nullable()->comment('屬性');
            $table->longText('model_data')->nullable()->comment('異動資料 model');
            $table->timestamps();
            $table->index(['variant_id', 'location_id']); // 索引
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
    }
};