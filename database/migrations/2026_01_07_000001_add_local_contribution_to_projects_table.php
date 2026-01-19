<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'local_contribution')) {
                $table->decimal('local_contribution', 15, 2)->default(0)->after('amount_forwarded');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'local_contribution')) {
                $table->dropColumn('local_contribution');
            }
        });
    }
};


