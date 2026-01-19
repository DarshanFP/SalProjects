<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Removes the provincial_coordinator_id field from provinces table.
     * This field is being removed because coordinators should have access
     * to ALL provinces by default (no assignment needed). Provinces are
     * managed by provincial users (role='provincial') who are children
     * of coordinators or general users.
     *
     * NOTE: This migration should run AFTER the data migration that
     * converts existing coordinator assignments to provincial users.
     */
    public function up(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['provincial_coordinator_id']);

            // Drop the index
            $table->dropIndex(['provincial_coordinator_id']);

            // Drop the column
            $table->dropColumn('provincial_coordinator_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Restores the provincial_coordinator_id field. Note that any
     * data that was in this field before removal will be lost.
     */
    public function down(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            // Add the column back
            $table->unsignedBigInteger('provincial_coordinator_id')->nullable()->after('name');

            // Add the index
            $table->index('provincial_coordinator_id');

            // Add the foreign key constraint
            $table->foreign('provincial_coordinator_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }
};
