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
        Schema::create('abonnes_web_models', function (Blueprint $table) {
            $table->id();
            $table->string('account_code_unique', 50);
            $table->string('category_code', 50);
            $table->string('full_name');
            $table->string('phone');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('two_factor_secret');
            $table->timestamp('two_factor_code_sent_at')->nullable();
            $table->boolean('email_verified_at')->default(false);
            $table->string('device_browser');
            $table->string('device_type');
            $table->string('device_os');
            $table->dateTime('last_login_at')->nullable();
            $table->dateTime('last_logout_at')->nullable();
            $table->boolean('status')->default(true);
            $table->string('slug', 100)->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abonnes_web_models');
    }
};
