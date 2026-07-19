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
        Schema::create('full_countrie_models', function (Blueprint $table) {
            $table->id();
            $table->string('country_code');
            $table->string('countrie_name');
            $table->string('phone_code');
            $table->string('flag');
            $table->string('currency');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('full_countrie_models');
    }
};
