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
        Schema::create('api_partner_news_log_models', function (Blueprint $table) {
            $table->id();
            $table->string('partner_code_unique')->comment('Code unique de l\'entreprise');
            $table->string('endpoint')->comment('Endpoint de l\'API');
            $table->string('http_method')->comment('Méthode HTTP');
            $table->integer('response_status')->comment('Code de réponse');
            $table->string('ip_address')->comment('Adresse IP');
            $table->string('user_agent')->comment('User Agent');
            $table->json('query_params')->comment('Paramètres de la requête');
            $table->longText('error_message')->nullable()->comment('Message d\'erreur');
            $table->timestamp('requested_at')->comment('Date de la requête');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_partner_news_log_models');
    }
};
