<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Production Phase 4: Enforce projects.society_id NOT NULL + FK to societies(id) ON DELETE RESTRICT.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE projects MODIFY society_id BIGINT UNSIGNED NOT NULL');
        } else {
            Schema::table('projects', function (Blueprint $table) {
                $table->unsignedBigInteger('society_id')->nullable(false)->change();
            });
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->foreign('society_id')
                ->references('id')
                ->on('societies')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['society_id']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE projects MODIFY society_id BIGINT UNSIGNED NULL');
        } else {
            Schema::table('projects', function (Blueprint $table) {
                $table->unsignedBigInteger('society_id')->nullable()->change();
            });
        }
    }
};
