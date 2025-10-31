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
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('name');
            $table->longText('settings')->nullable();
            $table->string('status')->nullable()->comment('狀態');
            $table->timestamps();
        });

        Schema::create('channelables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->onDelete('cascade');
            $table->nullableUlidMorphs('channelable'); // channelable_id + channelable_type
            $table->string('preferences')->nullable()->comment('個別設定，例如頻率、事件類別');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channelables');
        Schema::dropIfExists('channels');
    }
};
