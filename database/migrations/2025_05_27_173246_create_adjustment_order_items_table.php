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
        Schema::create('adjustment_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adjustment_order_id')->constrained('adjustment_orders');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('variants')->nullOnDelete();
            $table->string('product_name')->nullable()->comment('商品名稱');
            $table->string('variant_name')->nullable()->comment('商品規格');
            $table->integer('quantity')->comment('盤點數量');
            $table->integer('original_quantity')->nullable()->comment('原始數量');
            $table->integer('difference_quantity')->nullable()->comment('差異數量');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adjustment_order_items');
    }
};
