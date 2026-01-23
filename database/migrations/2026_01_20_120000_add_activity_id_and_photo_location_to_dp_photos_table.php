<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Phase 1 — Photo Rearrangement: activity mapping and EXIF location.
     */
    public function up(): void
    {
        Schema::table('DP_Photos', function (Blueprint $table) {
            $table->string('activity_id')->nullable()->after('report_id');
            $table->string('photo_location', 500)->nullable()->after('description');

            $table->foreign('activity_id')
                ->references('activity_id')
                ->on('DP_Activities')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('DP_Photos', function (Blueprint $table) {
            $table->dropForeign(['activity_id']);
            $table->dropColumn(['activity_id', 'photo_location']);
        });
    }
};
