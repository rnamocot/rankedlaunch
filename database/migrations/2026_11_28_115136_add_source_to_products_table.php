<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Directory listings imported from outside sources (e.g. Hacker News "Show HN").
 * A product with a source is listed, not launched: it never enters rankings or awards.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('source', 20)->nullable()->index();
            $table->string('source_id', 40)->nullable();
            $table->string('source_url')->nullable();
            $table->string('source_author', 60)->nullable();
            $table->unique(['source', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['source', 'source_id']);
            $table->dropIndex(['source']);
            $table->dropColumn(['source', 'source_id', 'source_url', 'source_author']);
        });
    }
};
