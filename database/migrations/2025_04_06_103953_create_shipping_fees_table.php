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
        Schema::create('shipping_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->unsignedBigInteger('logistic_id')->nullable()->comment('物流 ID');
            $table->string('name')->comment('運費名稱');
            $table->decimal('price', 10, 2)->default(0)->comment('運費金額');
            $table->decimal('total_discount', 10, 2)->default(0)->comment('運費折扣');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_fees');
    }
};
