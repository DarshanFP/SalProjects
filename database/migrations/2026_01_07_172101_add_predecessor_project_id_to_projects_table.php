<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add predecessor_project_id column to link projects to their predecessor projects
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('predecessor_project_id')->nullable()->after('goal');
            $table->foreign('predecessor_project_id')->references('project_id')->on('projects')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['predecessor_project_id']);
            $table->dropColumn('predecessor_project_id');
        });
    }
};
