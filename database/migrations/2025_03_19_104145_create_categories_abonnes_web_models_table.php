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
        Schema::create('categories_abonnes_web_models', function (Blueprint $table) {
            $table->id();
            $table->string('category_code', 50);
            $table->string('categorie');
            $table->boolean('can_copy')->default(false);
            $table->boolean('can_share')->default(false);
            $table->boolean('can_read')->default(false);
            $table->boolean('can_download')->default(false);
            $table->string('slug', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories_abonnes_web_models');
    }
};
