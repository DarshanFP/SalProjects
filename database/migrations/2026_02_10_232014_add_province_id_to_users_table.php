<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds users.province_id (nullable, indexed, FK to provinces.id).
     * No cascade on delete — provinces cannot be deleted.
     * Does NOT drop users.province (kept for dual-write/rollback).
     */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'province_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('province_id')->nullable()->index()->after('province');
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
        if (!Schema::hasColumn('users', 'province_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropIndex(['province_id']);
            $table->dropColumn('province_id');
        });
    }
};
