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
        Schema::create('transfer_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_order_id')->constrained('transfer_orders');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('variants')->nullOnDelete();
            $table->string('product_name')->nullable()->comment('商品名稱');
            $table->string('variant_name')->nullable()->comment('商品規格');
            $table->integer('quantity')->comment('轉移數量');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_order_items');
    }
};
