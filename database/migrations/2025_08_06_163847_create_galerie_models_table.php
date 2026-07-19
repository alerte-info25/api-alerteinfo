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
            $table->string('galerie_code_unique')->nullable();
            $table->string('title');
            $table->string('media_path');
            $table->uuid('slug');
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
