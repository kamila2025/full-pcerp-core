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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('variants')->nullOnDelete();
            $table->unsignedBigInteger('template_item_id')->nullable();
            $table->unsignedBigInteger('discount_id')->nullable();
            $table->string('product_name')->nullable()->comment('商品名稱');
            $table->string('variant_name')->nullable()->comment('商品規格');
            $table->string('sku')->nullable()->comment('商品編號');
            $table->string('barcode')->nullable()->comment('商品條碼');
            $table->decimal('price', 10, 2)->default(0)->comment('單價');
            $table->decimal('cost_price', 10, 2)->default(0)->comment('成本');
            $table->integer('quantity')->comment('數量');
            $table->decimal('subtotal', 10, 2)->default(0)->comment('小計');
            $table->decimal('total_discount', 10, 2)->default(0)->comment('折扣金額');
            $table->decimal('total_tax', 10, 2)->default(0)->comment('稅金');
            $table->decimal('total_amount', 10, 2)->default(0)->comment('總金額');
            $table->longText('properties')->nullable()->comment('訂單屬性');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
