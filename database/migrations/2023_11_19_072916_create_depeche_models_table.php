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
        Schema::create('depeche_models', function (Blueprint $table) {
            $table->id();
            $table->string('author')->nullable();
            $table->unsignedInteger('rubrique_id');
            $table->unsignedInteger('pays_id');
            $table->unsignedInteger('genre_id');
            $table->text('titre')->nullable();
            $table->text('lead')->nullable();
            $table->text('legende')->nullable();
            $table->string('media_url')->nullable();
            $table->longText('contenus')->nullable();
            $table->integer('counter')->default(0);
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
        Schema::dropIfExists('depeche_models');
    }
};
