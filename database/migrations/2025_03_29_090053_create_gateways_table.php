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
        Schema::create('gateways', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable()->comment('支付類型');
            $table->string('title')->nullable()->comment('支付名稱');
            $table->longText('body_html')->nullable()->comment('支付內容');
            $table->longText('additional')->nullable()->comment('額外資訊');
            $table->longText('sub_gateways')->nullable()->comment('子支付方式');
            $table->integer('position')->default(0);
            $table->boolean('is_enabled')->nullable()->comment('是否啟用');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gateways');
    }
};
