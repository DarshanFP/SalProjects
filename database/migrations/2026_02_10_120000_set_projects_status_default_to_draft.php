<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Set projects.status default to 'draft' for alignment with application behavior.
 * Does not modify existing rows; only alters the column default.
 * Note: On SQLite/PostgreSQL, ->change() may require doctrine/dbal. MySQL works without it.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('status')->default('draft')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('status')->default('underwriting')->change();
        });
    }
};
