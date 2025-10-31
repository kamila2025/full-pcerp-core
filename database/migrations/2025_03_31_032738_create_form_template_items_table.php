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
        Schema::create('form_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->constrained('form_templates')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('attribute_name')->comment('自訂欄位名稱');
            $table->string('default_value')->nullable()->comment('預設值');
            $table->integer('position')->default(0)->comment('排序');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_template_items');
    }
};
