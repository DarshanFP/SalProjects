<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            // Note: society_id is nullable because centers belong to provinces
            // All centers in a province are available to all societies in that province
            $table->unsignedBigInteger('society_id')->nullable()->after('id');

            $table->foreign('society_id')
                ->references('id')
                ->on('societies')
                ->onDelete('set null'); // Set null instead of cascade since centers belong to provinces

            $table->index('society_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->dropForeign(['society_id']);
            $table->dropIndex(['society_id']);
            $table->dropColumn('society_id');
        });
    }
};
