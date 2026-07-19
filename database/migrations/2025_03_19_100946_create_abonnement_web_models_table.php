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
        Schema::create('abonnement_web_models', function (Blueprint $table) {
            $table->id();
            $table->string('abonnement_web_code');
            $table->string('account_code_unique');
            $table->unsignedInteger('forfait_id');
            $table->unsignedInteger('country_id');
            $table->integer('montant');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->string('country_code', 20)->nullable();
            $table->string('customer_city', 100)->nullable();
            $table->string('customer_address', 50)->nullable();
            $table->string('customer_zip_code', 50)->nullable();
            $table->string('customer_state', 50)->nullable();
            $table->boolean('payments')->default(false);
            $table->string('slug', 50)->unique();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abonnement_web_models');
    }
};
