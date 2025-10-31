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
        Schema::create('logistics', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->comment('物流提供者');
            $table->string('type')->comment('物流類型');
            $table->string('name')->comment('物流名稱');
            $table->decimal('free_price', 10, 2)->default(0)->comment('免運費金額');
            $table->decimal('fee', 10, 2)->default(0)->comment('運費');
            $table->integer('position')->default(0)->comment('排序');
            $table->longText('additional')->nullable()->comment('額外資訊');
            $table->boolean('is_enabled')->nullable()->comment('是否啟用');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics');
    }
};
