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
        Schema::table('project_CCI_age_profile', function (Blueprint $table) {
            $table->string('education_below_5_other_specify', 255)->nullable();
            $table->string('education_6_10_other_specify', 255)->nullable();
            $table->string('education_11_15_other_specify', 255)->nullable();
            $table->string('education_16_above_other_specify', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_CCI_age_profile', function (Blueprint $table) {
            $table->dropColumn([
                'education_below_5_other_specify',
                'education_6_10_other_specify',
                'education_11_15_other_specify',
                'education_16_above_other_specify',
            ]);
        });
    }
};
