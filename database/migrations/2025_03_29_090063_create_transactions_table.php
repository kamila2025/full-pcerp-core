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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('gateway_type')->nullable()->comment('支付類型');
            $table->string('gateway_title')->nullable()->comment('支付名稱');
            $table->string('gateway_method')->nullable()->comment('支付方式');
            $table->string('gateway_account')->nullable()->comment('支付帳號');
            $table->string('short_code')->unique()->nullable()->comment('短碼');
            $table->string('number')->unique()->nullable()->comment('交易編號');
            $table->decimal('amount', 10, 2)->default(0)->comment('金額');
            $table->decimal('fee', 10, 2)->default(0)->comment('交易手續費'); // 新增的欄位
            $table->string('currency')->nullable()->comment('貨幣');
            $table->longText('note')->nullable()->comment('備註');
            $table->boolean('testcase')->default(false)->comment('測試案例');
            $table->timestamp('paid_at')->nullable()->comment('付款時間');
            $table->string('reference')->nullable()->comment('參考編號');
            $table->longText('additional')->nullable()->comment('額外資訊');
            $table->string('error_code')->nullable()->comment('錯誤代碼');
            $table->string('error_description')->nullable()->comment('錯誤描述');
            /**
             * 狀態:
             * - created: 已建立
             * - pending: 處理中
             * - success: 已付款
             * - failed: 失敗
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
        Schema::dropIfExists('transactions');
    }
};
