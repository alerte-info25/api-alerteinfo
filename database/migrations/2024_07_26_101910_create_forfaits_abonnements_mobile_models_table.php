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
        Schema::create('forfaits_abonnements_mobile_models', function (Blueprint $table) {
            $table->id();
            $table->string('forfait_libelle');
            $table->float('montant_forfait');
            $table->integer('duree_forfait');
            $table->string('slug', 50)->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forfaits_abonnements_mobile_models');
    }
};
