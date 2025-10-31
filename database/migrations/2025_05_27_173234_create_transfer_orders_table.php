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
        Schema::create('transfer_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_user_id')->constrained('users')->comment('調撥人員');
            $table->foreignId('receiver_user_id')->nullable()->constrained('users')->comment('點收人員');
            $table->foreignId('from_location_id')->constrained('locations')->comment('原始分店');
            $table->foreignId('to_location_id')->constrained('locations')->comment('目的分店');
            $table->string('order_number')->comment('轉移單號碼');
            $table->date('delivery_date')->nullable()->comment('送貨日期');
            $table->longText('remark')->nullable()->comment('備註');
            $table->string('arrival_status')->comment('到貨狀態');
            $table->string('status')->comment('調撥狀態');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_orders');
    }
};
