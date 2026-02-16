<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Allows global societies (province_id = NULL) per Phase Plan Revision 5.
     */
    public function up(): void
    {
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
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
                ->onDelete('cascade');
        });
    }
};
