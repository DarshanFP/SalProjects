<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Phase 4: Add projects.society_id (nullable initially, indexed, FK to societies.id).
     * Backfill and NOT NULL enforced in separate steps.
     */
    public function up(): void
    {
        if (Schema::hasColumn('projects', 'society_id')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('society_id')->nullable()->index()->after('province_id');
            $table->foreign('society_id')
                ->references('id')
                ->on('societies')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('projects', 'society_id')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['society_id']);
            $table->dropIndex(['society_id']);
            $table->dropColumn('society_id');
        });
    }
};
