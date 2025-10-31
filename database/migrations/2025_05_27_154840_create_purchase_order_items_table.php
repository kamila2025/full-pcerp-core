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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders');
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('variants')->nullOnDelete();
            $table->string('product_name')->nullable()->comment('商品名稱');
            $table->string('variant_name')->nullable()->comment('商品規格');
            $table->string('sku')->unique()->nullable()->comment('編號');
            $table->string('barcode')->unique()->nullable()->comment('條碼');
            $table->integer('quantity')->comment('數量');
            $table->decimal('price', 10, 2)->default(0)->comment('單價');
            $table->decimal('cost_price', 10, 2)->default(0)->comment('進價');
            $table->decimal('total_cost', 10, 2)->default(0)->comment('總成本');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
