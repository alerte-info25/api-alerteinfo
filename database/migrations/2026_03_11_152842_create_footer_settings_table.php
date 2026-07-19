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
        Schema::create('footer_settings', function (Blueprint $table) {
            $table->id();
             // Textes de description
            $table->text('description_1')->nullable();
            $table->text('description_2')->nullable();
            $table->text('description_3')->nullable();

            // Contacts téléphoniques (tableau JSON : [{label, number}])
            $table->json('phones')->nullable();

            // Emails
            $table->string('email_direction')->nullable();
            $table->string('email_redaction')->nullable();

            // Adresse Abidjan
            $table->string('address_abidjan_city')->nullable();
            $table->text('address_abidjan_detail')->nullable();

            // Adresse Ouagadougou
            $table->string('address_ouaga_city')->nullable();
            $table->text('address_ouaga_detail')->nullable();

            // Réseaux sociaux
            $table->string('facebook_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('footer_settings');
    }
};
