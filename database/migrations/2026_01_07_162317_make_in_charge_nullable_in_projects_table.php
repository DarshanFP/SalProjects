<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Make goal nullable to support draft saves
     * Note: in_charge is kept as NOT NULL since we always set it to the logged-in user
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('goal')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Set default empty string before making non-nullable
            DB::statement('UPDATE projects SET goal = "" WHERE goal IS NULL');
            
            $table->text('goal')->nullable(false)->change();
        });
    }
};
