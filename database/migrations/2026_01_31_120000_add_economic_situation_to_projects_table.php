<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add economic_situation column to Key Information section (before goal)
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('economic_situation')->nullable()->before('goal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('economic_situation');
        });
    }
};
