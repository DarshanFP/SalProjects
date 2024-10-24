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
        Schema::create('project_CCI_personal_situation', function (Blueprint $table) {
            $table->id();
            $table->string('CCI_personal_situation_id')->unique();
            $table->string('project_id'); // Foreign key to project

            // Children with parents
            $table->integer('children_with_parents_last_year')->nullable();
            $table->integer('children_with_parents_current_year')->nullable();

            // Semi-orphans (living with relatives)
            $table->integer('semi_orphans_last_year')->nullable();
            $table->integer('semi_orphans_current_year')->nullable();

            // Orphans
            $table->integer('orphans_last_year')->nullable();
            $table->integer('orphans_current_year')->nullable();

            // HIV-infected/affected
            $table->integer('hiv_infected_last_year')->nullable();
            $table->integer('hiv_infected_current_year')->nullable();

            // Differently-abled children
            $table->integer('differently_abled_last_year')->nullable();
            $table->integer('differently_abled_current_year')->nullable();

            // Parents in conflict
            $table->integer('parents_in_conflict_last_year')->nullable();
            $table->integer('parents_in_conflict_current_year')->nullable();

            // Other ailments
            $table->integer('other_ailments_last_year')->nullable();
            $table->integer('other_ailments_current_year')->nullable();

            // General remarks
            $table->text('general_remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_CCI_personal_situation');
    }
};
