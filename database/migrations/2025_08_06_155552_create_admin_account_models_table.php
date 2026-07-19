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
        Schema::create('admin_account_models', function (Blueprint $table) {
            $table->id();
            $table->string('account_code_unique');
            $table->string('role_code_unique');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->string('email');
            $table->string('password');
            $table->string('photo')->nullable();
            $table->string('two_factor_secret')->nullable();
            $table->string('two_factor_code_sent_at')->nullable();
            $table->string('two_factor_enabled')->nullable();
            $table->string('email_verified_at')->nullable();
            $table->string('device_browser')->nullable();
            $table->string('device_type')->nullable();
            $table->string('device_os')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->dateTime('last_logout_at')->nullable();
            $table->boolean('connected')->default(0);
            $table->enum('status', ['Actif', 'Inactif'])->default('Actif');
            $table->softDeletes();
            $table->uuid('slug');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_account_models');
    }
};
