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
        Schema::create('web_solde_models', function (Blueprint $table) {
            $table->id();
            $table->integer('montants')->default(00);
            $table->integer('montants_net')->default(00);
            $table->integer('amount_transferred')->default(00);
            $table->string('slug')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('web_solde_models');
    }
};
