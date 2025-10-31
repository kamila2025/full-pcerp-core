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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable()->comment('客戶');
            $table->unsignedBigInteger('attribution_user_id')->nullable()->comment('歸屬業務');
            $table->unsignedBigInteger('location_id')->nullable()->comment('訂單來源地點');
            $table->unsignedBigInteger('shipping_location_id')->nullable()->comment('出貨地點');
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('source_type')->comment('來源類型 (web, app, api, others)');
            $table->string('order_number')->comment('訂單編號');
            $table->string('delivery_type')->comment('配送方式 (shipping, pickup, others)');
            $table->boolean('tax_included')->default(false)->comment('是否含稅');
            $table->decimal('tax_rate', 10, 2)->default(0)->comment('稅率');
            $table->decimal('subtotal_price', 10, 2)->default(0)->comment('小計');
            $table->decimal('total_discount', 10, 2)->default(0)->comment('折扣');
            $table->decimal('custom_discount', 10, 2)->default(0)->comment('自訂折扣');
            $table->decimal('shipping_discount', 10, 2)->default(0)->comment('運費折扣');
            $table->decimal('total_shipping', 10, 2)->default(0)->comment('運費');
            $table->decimal('total_tax', 10, 2)->default(0)->comment('稅金');
            $table->decimal('total_price', 10, 2)->default(0)->comment('總計');
            $table->decimal('custom_amount', 10, 2)->default(0)->comment('自訂金額');
            $table->decimal('amount', 10, 2)->default(0)->comment('金額');
            // 額外資訊
            $table->string('cart_token')->nullable()->comment('購物車 Token');
            $table->string('barcode')->nullable()->comment('條碼');
            $table->string('full_name')->nullable()->comment('姓名');
            $table->string('email')->nullable()->comment('Email');
            $table->string('phone')->nullable()->comment('電話');
            $table->longText('note')->nullable()->comment('商家備註');
            $table->longText('remark')->nullable()->comment('顧客備註');
            /**
             * 出貨狀態:
             * - unfulfilled: 尚未出貨
             * - partially_fulfilled: 部分已出貨
             * - fulfilled: 全部已出貨
             * - partially_delivered: 部分已送達
             * - delivered: 已全部送達
             * - restocked: 重新入庫
             * - pickup: 客戶自取
             */
            $table->string('fulfillment_status')->nullable()->comment('出貨狀態');
            /**
             * 付款狀態:
             * - unpaid: 未付款
             * - cod: 貨到付款
             * - partially_paid: 部分已付款
             * - paid: 已全額付款
             * - refunded: 已退款
             */
            $table->string('financial_status')->nullable()->comment('付款狀態');
            /**
             * 狀態:
             * - open: 開啟中 / 處理中
             * - cancelled: 已取消
             * - archived: 已封存 / 歸檔
             * - deleted: 已刪除
             */
            $table->string('status')->nullable()->comment('狀態');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
