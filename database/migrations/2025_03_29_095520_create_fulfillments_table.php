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
        Schema::create('fulfillments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('service')->nullable()->comment('服務');
            $table->string('tracking_company')->nullable()->comment('運送公司');
            $table->string('tracking_number')->nullable()->comment('運送單號');
            $table->string('tracking_url')->nullable()->comment('運送網址');
            $table->string('consignment_note_url')->nullable()->comment('運送單網址');
            $table->longText('message')->nullable()->comment('訊息');
            $table->string('status')->nullable()->comment('狀態');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fulfillments');
    }
};
