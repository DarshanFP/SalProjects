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
        Schema::table('rqwd_objectives', function (Blueprint $table) {
            //
            $table->string('objective')->nullable()->after('report_id'); // Add this line

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rqwd_objectives', function (Blueprint $table) {
            //
        });
    }
};
