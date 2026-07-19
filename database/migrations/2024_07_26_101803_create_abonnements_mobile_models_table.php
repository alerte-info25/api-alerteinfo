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
        Schema::create('abonnements_mobile_models', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('abonne_id');
            $table->unsignedInteger('abonne_forfait_id');
            $table->string('abonne_country_id');
            $table->integer('montant_abonnements');
            $table->date('date_debut');
            $table->date('date_fin');
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
        Schema::dropIfExists('abonnements_mobile_models');
    }
};
