<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds CHECK constraints to enforce non-negative opening_balance and amount_sanctioned.
     * MariaDB 10.2.1+ / MySQL 8.0.16+ required.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE projects
            ADD CONSTRAINT chk_projects_opening_balance_non_negative
            CHECK (opening_balance >= 0),
            ADD CONSTRAINT chk_projects_amount_sanctioned_non_negative
            CHECK (amount_sanctioned >= 0)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE projects
            DROP CONSTRAINT chk_projects_opening_balance_non_negative,
            DROP CONSTRAINT chk_projects_amount_sanctioned_non_negative
        ");
    }
};
