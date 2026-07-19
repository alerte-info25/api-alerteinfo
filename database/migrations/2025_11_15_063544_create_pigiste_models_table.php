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
        Schema::create('pigiste_models', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('pigiste_first_name')->nullable();
            $table->string('pigiste_last_name')->nullable();
            $table->string('pigiste_email')->nullable();
            $table->string('pigiste_phone')->nullable();
            $table->string('pigiste_address')->nullable();
            $table->string('pigiste_country')->nullable();
            $table->string('pigiste_speciality')->nullable();
            $table->string('pigiste_cv')->nullable();
            $table->string('pigiste_comment')->nullable();
            $table->boolean('pigiste_accept_terms')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pigiste_models');
    }
};
