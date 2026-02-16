<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Phase 3: Add projects.province_id (nullable, indexed, FK to provinces.id).
     * Backfill performed after migration. NOT NULL enforced in separate migration.
     */
    public function up(): void
    {
        if (Schema::hasColumn('projects', 'province_id')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('province_id')->nullable()->index()->after('user_id');
            $table->foreign('province_id')
                ->references('id')
                ->on('provinces')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('projects', 'province_id')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropIndex(['province_id']);
            $table->dropColumn('province_id');
        });
    }
};
