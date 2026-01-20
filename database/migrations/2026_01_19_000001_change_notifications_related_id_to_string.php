<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Change related_id from bigint to string to support project_id (e.g. "DP-0025")
     * and report_id string identifiers.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE notifications MODIFY related_id VARCHAR(255) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE notifications ALTER COLUMN related_id TYPE VARCHAR(255) USING related_id::text');
        }
        // SQLite: column type change would require table recreate; if needed, add separately
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE notifications MODIFY related_id BIGINT UNSIGNED NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE notifications ALTER COLUMN related_id TYPE BIGINT USING NULLIF(related_id, \'\')::bigint');
        }
    }
};
