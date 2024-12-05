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
        Schema::table('DP_Objectives', function (Blueprint $table) {
            $table->string('project_objective_id')->nullable()->after('objective_id');
            


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('DP_Objectives', function (Blueprint $table) {
            //
        });
    }
};
