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
        Schema::create('articles_models', function (Blueprint $table) {
            $table->id();
            $table->integer('rubrique_id')->unsigned()->nullable();
            $table->integer('genre_id')->unsigned()->nullable();
            $table->integer('pays_id')->unsigned()->nullable();
            $table->text('titre')->nullable();
            $table->text('lead')->nullable();
            $table->string('author')->nullable();
            $table->text('contenus')->nullable();
            $table->string('media_url')->nullable();
            $table->text('legende')->nullable();
            $table->integer('like_counter')->default(0);
            $table->integer('dislike_counter')->default(0);
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
        Schema::dropIfExists('articles_models');
    }
};
