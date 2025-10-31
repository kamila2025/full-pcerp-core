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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribution_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('attribution_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source')->index()->nullable()->comment('來源: admin, facebook, line, sf');
            $table->string('code')->unique()->nullable()->comment('客戶代號');
            $table->string('name')->nullable()->comment('名字');
            $table->string('email')->unique()->nullable()->comment('電子郵件');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone')->unique()->nullable()->comment('手機號碼');
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('gender')->nullable()->comment('性別');
            $table->string('country_code')->nullable()->comment('國碼');
            $table->string('country')->nullable()->comment('國家');
            $table->date('birthday')->nullable()->comment('生日');
            $table->longText('note')->nullable()->comment('筆記');
            $table->longText('additional')->nullable()->comment('額外資訊');
            $table->string('status')->nullable()->comment('狀態: blacklisted, verified, unverified');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
