<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wave 6A Phase 4: Enforce NOT NULL and FK on report society snapshot.
 * Run only after Phase 3 verification passes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('DP_Reports', function (Blueprint $table) {
            $table->unsignedBigInteger('society_id')->nullable(false)->change();
            $table->string('society_name')->nullable(false)->change();
            $table->unsignedBigInteger('province_id')->nullable(false)->change();
            $table->foreign('society_id')
                ->references('id')
                ->on('societies')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('DP_Reports', function (Blueprint $table) {
            $table->dropForeign(['society_id']);
            $table->unsignedBigInteger('society_id')->nullable()->change();
            $table->string('society_name')->nullable()->change();
            $table->unsignedBigInteger('province_id')->nullable()->change();
        });
    }
};
