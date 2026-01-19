<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChangeProvinceToStringInUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL/MariaDB, we need to alter the column from enum to string
        Schema::table('users', function (Blueprint $table) {
            // Using DB raw query for MySQL enum to string conversion
            DB::statement("ALTER TABLE users MODIFY province VARCHAR(255) NULL");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert back to enum with existing values
            DB::statement("ALTER TABLE users MODIFY province ENUM('Bangalore', 'Vijayawada', 'Visakhapatnam', 'Generalate', 'Divyodaya', 'Indonesia', 'East Timor', 'East Africa', 'Luzern', 'none') NULL");
        });
    }
}
