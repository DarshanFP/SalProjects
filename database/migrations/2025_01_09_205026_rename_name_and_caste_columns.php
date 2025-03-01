<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('project_IES_personal_info', function (Blueprint $table) {
            // Add new columns in the correct order
            $table->string('bname')->nullable()->after('project_id');
            $table->string('bcaste')->nullable()->after('current_studies');
        });

        // Copy data from the old columns to the new columns
        DB::statement('UPDATE project_IES_personal_info SET bname = name, bcaste = caste');

        // Drop the old columns
        Schema::table('project_IES_personal_info', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->dropColumn('caste');
        });
    }

    public function down(): void
    {
        Schema::table('project_IES_personal_info', function (Blueprint $table) {
            // Add back the original columns
            $table->string('name')->nullable()->after('project_id');
            $table->string('caste')->nullable()->after('current_studies');
        });

        // Copy data back to the original columns
        DB::statement('UPDATE project_IES_personal_info SET name = bname, caste = bcaste');

        // Drop the new columns
        Schema::table('project_IES_personal_info', function (Blueprint $table) {
            $table->dropColumn('bname');
            $table->dropColumn('bcaste');
        });
    }
};
