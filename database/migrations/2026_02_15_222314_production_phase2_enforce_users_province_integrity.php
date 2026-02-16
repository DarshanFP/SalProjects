<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Production Phase 2: Enforce users.province_id NOT NULL + FK to provinces(id) ON DELETE RESTRICT.
     * Users table only. No data changes. Pre-condition: 0 NULL, 0 orphans.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY province_id BIGINT UNSIGNED NOT NULL');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('province_id')->nullable(false)->change();
            });
        }

        Schema::table('users', function (Blueprint $table) {
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
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE users MODIFY province_id BIGINT UNSIGNED NULL');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('province_id')->nullable()->change();
            });
        }
    }
};
