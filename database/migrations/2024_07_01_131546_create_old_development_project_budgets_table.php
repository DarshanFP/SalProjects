<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOldDevelopmentProjectBudgetsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('old_DP_budgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedInteger('phase');
            $table->string('description');
            $table->decimal('rate_quantity', 10, 2);
            $table->decimal('rate_multiplier', 10, 2);
            $table->decimal('rate_duration', 10, 2);
            $table->decimal('rate_increase', 10, 2)->nullable();
            $table->decimal('this_phase', 10, 2);
            $table->decimal('next_phase', 10, 2);
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('oldDevelopmentProjects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('old_DP_budgets');
    }
}
