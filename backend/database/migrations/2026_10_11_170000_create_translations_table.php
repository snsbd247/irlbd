<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per (record, language, field). The default language (English) stays in
        // the records' own columns; other languages live here and fall back to English.
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('translatable_type', 60);
            $table->unsignedBigInteger('translatable_id');
            $table->string('locale', 5);
            $table->string('field', 60);
            $table->longText('value')->nullable();
            $table->timestamps();
            $table->unique(['translatable_type', 'translatable_id', 'locale', 'field'], 'translations_unique');
            $table->index(['translatable_type', 'translatable_id', 'locale'], 'translations_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
