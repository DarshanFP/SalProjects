<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Production Phase 1: Move from composite (province_id + name) to global unique(name).
     * Societies table only. No data changes.
     * Idempotent: skips drop/add if index was already changed by enforce_unique_name_on_societies.
     */
    public function up(): void
    {
        if ($this->indexExists('societies', 'unique_province_society')) {
            Schema::table('societies', function (Blueprint $table) {
                $table->dropUnique('unique_province_society');
            });
        }

        Schema::table('societies', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE societies MODIFY province_id BIGINT UNSIGNED NULL');
        } else {
            Schema::table('societies', function (Blueprint $table) {
                $table->unsignedBigInteger('province_id')->nullable()->change();
            });
        }

        Schema::table('societies', function (Blueprint $table) {
            $table->foreign('province_id')
                ->references('id')
                ->on('provinces')
                ->onDelete('restrict');
        });

        if (!$this->indexExists('societies', 'societies_name_unique')) {
            Schema::table('societies', function (Blueprint $table) {
                $table->unique('name', 'societies_name_unique');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::selectOne(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [DB::getDatabaseName(), $table, $indexName]
        );
        return $result !== null;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('societies', function (Blueprint $table) {
            $table->dropUnique('societies_name_unique');
        });

        Schema::table('societies', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE societies MODIFY province_id BIGINT UNSIGNED NOT NULL');
        } else {
            Schema::table('societies', function (Blueprint $table) {
                $table->unsignedBigInteger('province_id')->nullable(false)->change();
            });
        }

        Schema::table('societies', function (Blueprint $table) {
            $table->foreign('province_id')
                ->references('id')
                ->on('provinces')
                ->onDelete('restrict');
        });

        Schema::table('societies', function (Blueprint $table) {
            $table->unique(['province_id', 'name'], 'unique_province_society');
        });
    }
};
