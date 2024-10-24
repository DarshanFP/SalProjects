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
        Schema::create('project_CCI_statistics', function (Blueprint $table) {
            $table->id();
            $table->string('CCI_statistics_id')->unique();
            $table->string('project_id'); // Foreign key to project

            // Previous and current year statistics
            $table->integer('total_children_previous_year');
            $table->integer('total_children_current_year');

            // Reintegration statistics
            $table->integer('reintegrated_children_previous_year');
            $table->integer('reintegrated_children_current_year');

            // Children shifted to other NGOs / Govt.
            $table->integer('shifted_children_previous_year');
            $table->integer('shifted_children_current_year');

            // Children pursuing higher studies outside
            $table->integer('pursuing_higher_studies_previous_year');
            $table->integer('pursuing_higher_studies_current_year');

            // Children who completed studies and settled down in life (e.g., married)
            $table->integer('settled_children_previous_year');
            $table->integer('settled_children_current_year');

            // Children who are now settled and working
            $table->integer('working_children_previous_year');
            $table->integer('working_children_current_year');

            // Any other category
            $table->integer('other_category_previous_year')->nullable();
            $table->integer('other_category_current_year')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_CCI_statistics');
    }
};
