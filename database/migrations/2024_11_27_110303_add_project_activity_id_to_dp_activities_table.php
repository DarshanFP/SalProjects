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
        Schema::table('DP_Activities', function (Blueprint $table) {
            $table->string('project_activity_id')->nullable()->after('objective_id');
            $table->string('activity')->nullable()->after('project_activity_id'); // Add this if you want to store the activity description


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('DP_Activities', function (Blueprint $table) {
            //
        });
    }
};
