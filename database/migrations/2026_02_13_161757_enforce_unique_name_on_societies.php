<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Enforce global unique society name.
 *
 * - Drops composite unique (province_id, name).
 * - Adds unique index on name.
 * - province_id remains nullable; index on province_id unchanged.
 */
return new class extends Migration
{
    /** Composite unique index name from create_societies_table migration. */
    private const COMPOSITE_UNIQUE_INDEX = 'unique_province_society';

    /** Laravel default name for unique index on societies.name. */
    private const NAME_UNIQUE_INDEX = 'societies_name_unique';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('societies', function (Blueprint $table) {
            $table->dropUnique(self::COMPOSITE_UNIQUE_INDEX);
            $table->unique('name', self::NAME_UNIQUE_INDEX);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('societies', function (Blueprint $table) {
            $table->dropUnique(self::NAME_UNIQUE_INDEX);
            $table->unique(['province_id', 'name'], self::COMPOSITE_UNIQUE_INDEX);
        });
    }
};
