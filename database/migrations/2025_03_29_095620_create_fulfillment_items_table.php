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
        Schema::create('fulfillment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fulfillment_id')->constrained('fulfillments')->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained('order_items')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name')->nullable()->comment('商品名稱');
            $table->string('variant_name')->nullable()->comment('商品規格');
            $table->string('sku')->nullable()->comment('商品編號');
            $table->string('barcode')->nullable()->comment('商品條碼');
            $table->decimal('price', 10, 2)->default(0)->comment('單價');
            $table->decimal('total_discount', 10, 2)->default(0)->comment('折扣金額');
            $table->integer('fulfilled_quantity')->comment('出貨數量');
            $table->longText('additional')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fulfillment_items');
    }
};
