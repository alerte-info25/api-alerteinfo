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
        Schema::create('api_partner_news_models', function (Blueprint $table) {
            $table->id();
            $table->string('partner_code_unique')->comment('Code unique de l\'entreprise');
            $table->string('name')->comment('Nom de l\'entreprise'); // ex: "PresseInfo Ltd"
            $table->string('email')->unique()->comment('Email de l\'entreprise'); // contact
            $table->string('api_token')->unique()->comment('Token unique'); // token unique
            $table->integer('rate_limit')->default(70)->comment('Requêtes/minute'); // requêtes/minute
            $table->boolean('is_active')->default(true)->comment('Statut');
            $table->timestamp('last_used_at')->nullable()->comment('Dernière utilisation');
            $table->uuid('slug');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_partner_news_models');
    }
};
