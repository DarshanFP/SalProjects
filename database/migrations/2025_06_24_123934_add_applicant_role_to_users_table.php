<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, we need to drop the existing enum constraint and recreate it
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'coordinator', 'provincial', 'executor', 'general', 'applicant') DEFAULT 'executor'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'applicant' from the enum
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'coordinator', 'provincial', 'executor', 'general') DEFAULT 'executor'");
    }
};
