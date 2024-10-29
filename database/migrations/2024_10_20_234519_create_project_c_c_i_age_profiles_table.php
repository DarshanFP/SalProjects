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
        Schema::create('project_CCI_age_profile', function (Blueprint $table) {
            $table->id();
            $table->string('CCI_age_profile_id')->unique();
            $table->string('project_id'); // Foreign key to project
            // $table->string(column: 'age_category')->nullable(); // Age category (e.g., below 5, 6-10, etc.)

            // Children below 5 years
            $table->integer('education_below_5_bridge_course_prev_year')->nullable();
            $table->integer('education_below_5_bridge_course_current_year')->nullable();
            $table->integer('education_below_5_kindergarten_prev_year')->nullable();
            $table->integer('education_below_5_kindergarten_current_year')->nullable();
            $table->string('education_below_5_other_prev_year')->nullable();
            $table->string('education_below_5_other_current_year')->nullable();

            // Children between 6 to 10 years
            $table->integer('education_6_10_primary_school_prev_year')->nullable();
            $table->integer('education_6_10_primary_school_current_year')->nullable();
            $table->integer('education_6_10_bridge_course_prev_year')->nullable();
            $table->integer('education_6_10_bridge_course_current_year')->nullable();
            $table->string('education_6_10_other_prev_year')->nullable();
            $table->string('education_6_10_other_current_year')->nullable();

            // Children between 11 to 15 years
            $table->integer('education_11_15_secondary_school_prev_year')->nullable();
            $table->integer('education_11_15_secondary_school_current_year')->nullable();
            $table->integer('education_11_15_high_school_prev_year')->nullable();
            $table->integer('education_11_15_high_school_current_year')->nullable();
            $table->string('education_11_15_other_prev_year')->nullable();
            $table->string('education_11_15_other_current_year')->nullable();

            // 16 and above
            $table->integer('education_16_above_undergraduate_prev_year')->nullable();
            $table->integer('education_16_above_undergraduate_current_year')->nullable();
            $table->integer('education_16_above_technical_vocational_prev_year')->nullable();
            $table->integer('education_16_above_technical_vocational_current_year')->nullable();
            $table->string('education_16_above_other_prev_year')->nullable();
            $table->string('education_16_above_other_current_year')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_CCI_age_profile');
    }
};
