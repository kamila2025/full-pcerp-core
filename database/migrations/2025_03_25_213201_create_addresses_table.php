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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->string('address_type')->comment('地址類型 (customer, order)');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->cascadeOnDelete();
            $table->string('type')->nullable()->comment('訂單類型 (billing, shipping, pickup)');
            $table->string('full_name')->nullable()->comment('姓名');
            $table->string('first_name')->nullable()->comment('名');
            $table->string('last_name')->nullable()->comment('姓');
            $table->string('company')->nullable()->comment('公司名稱');
            $table->string('address1')->nullable()->comment('地址1');
            $table->string('address2')->nullable()->comment('地址2');
            // 詳細地址
            $table->string('country_code')->nullable()->comment('國家代碼');
            $table->string('country')->nullable()->comment('國家');
            $table->string('province_code')->nullable()->comment('省份代碼');
            $table->string('province')->nullable()->comment('省份');
            $table->string('district_code')->nullable()->comment('區域代碼');
            $table->string('district')->nullable()->comment('區域');
            $table->string('city')->nullable()->comment('鄉/鎮/市/區');
            $table->string('postcode')->nullable()->comment('郵遞區號');
            // 資訊
            $table->string('email')->nullable()->comment('信箱');
            $table->string('phone')->nullable()->comment('電話');
            $table->string('vat_id')->nullable()->comment('統一編號');
            $table->boolean('is_primary')->default(false)->comment('僅適用於顧客地址');
            $table->json('additional')->nullable()->comment('額外資訊');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
