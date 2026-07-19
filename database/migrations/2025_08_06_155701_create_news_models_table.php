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
        Schema::create('news_models', function (Blueprint $table) {
            $table->id();
            $table->string('news_code_unique');
            $table->string('rubrique_code_unique');
            $table->string('rubrique_category_code_unique');
            $table->string('news_title');
            $table->string('news_lead');
            $table->text('news_content');
            $table->string('media_path');
            $table->string('media_legend');
            $table->string('news_author');
            $table->integer('news_views')->default(0);
            $table->enum('published', ['Brouillon', 'Publié'])->default('Brouillon');
            $table->softDeletes();
            $table->string('news_slug');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_models');
    }
};
