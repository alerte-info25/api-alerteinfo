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
        Schema::create('flashes_models', function (Blueprint $table) {
            $table->id();
            $table->string('rubrique_libelle')->nullable();
            $table->string('pays_id')->nullable();
            $table->string('author')->nullable();
            $table->string('media_url')->nullable();
            $table->text('contenus')->nullable();
            $table->boolean('status')->default(0);
            $table->string('slug')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flashes_models');
    }
};
