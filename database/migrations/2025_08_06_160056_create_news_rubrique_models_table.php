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
        Schema::create('news_rubrique_models', function (Blueprint $table) {
            $table->id();
            $table->string('rubrique_code_unique');
            $table->string('rubrique_name');
            $table->uuid('slug');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_rubrique_models');
    }
};
