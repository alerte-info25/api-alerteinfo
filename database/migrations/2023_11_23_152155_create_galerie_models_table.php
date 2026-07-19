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
        Schema::create('galerie_models', function (Blueprint $table) {
            $table->id();
            $table->string('galerie_libelle')->nullable();
            $table->text('galerie_description')->nullable();
            $table->string('galerie_img_url')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('galerie_models');
    }
};
