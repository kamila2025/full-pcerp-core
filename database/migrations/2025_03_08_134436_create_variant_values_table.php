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
        Schema::create('variant_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_type_id')->constrained('variant_types')->onDelete('cascade');
            $table->string('name');
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->unique(['variant_type_id', 'name']);
        });

        Schema::create('variant_value_has_variant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_value_id')->constrained('variant_values')->onDelete('cascade');
            $table->foreignId('variant_id')->constrained('variants')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_value_has_variant');
        Schema::dropIfExists('variant_values');
    }
};
