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
        Schema::create('abonnes_mobile_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('abonne_fname');
            $table->string('abonne_lname');
            $table->string('abonne_email')->unique();
            $table->string('abonne_phone_number')->unique();
            $table->string('type_abonne')->nullable();
            $table->boolean('status_abonnement')->default(0);
            $table->string('slug',100)->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abonnes_mobile_models');
    }
};
