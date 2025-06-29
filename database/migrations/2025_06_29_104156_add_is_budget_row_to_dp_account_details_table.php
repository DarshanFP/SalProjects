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
        Schema::table('DP_AccountDetails', function (Blueprint $table) {
            $table->boolean('is_budget_row')->default(false)->after('balance_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('DP_AccountDetails', function (Blueprint $table) {
            $table->dropColumn('is_budget_row');
        });
    }
};
