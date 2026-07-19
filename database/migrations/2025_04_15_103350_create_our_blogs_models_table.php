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
        Schema::create('our_blogs_models', function (Blueprint $table) {
            $table->id();
            $table->text('title');
            $table->text('lead');
            $table->longText('contents');
            $table->string('media_path');
            $table->uuid('slug');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('our_blogs_models');
    }
};
