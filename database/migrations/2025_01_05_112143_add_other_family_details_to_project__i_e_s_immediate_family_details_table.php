<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('project_IES_immediate_family_details', function (Blueprint $table) {
            $table->string('family_details_others')->nullable()->after('father_deserted');
        });
    }

    public function down(): void
    {
        Schema::table('project_IES_immediate_family_details', function (Blueprint $table) {
            $table->dropColumn('other_family_details');
        });
    }
};
