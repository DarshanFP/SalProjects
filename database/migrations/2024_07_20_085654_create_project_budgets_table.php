<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectBudgetsTable extends Migration
{
    public function up()
    {
        Schema::create('project_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('project_id');
            $table->integer('phase')->nullable();
            $table->string('particular')->nullable();
            $table->decimal('rate_quantity', 10, 2)->nullable();
            $table->decimal('rate_multiplier', 10, 2)->nullable();
            $table->decimal('rate_duration', 10, 2)->nullable();
            $table->decimal('rate_increase', 10, 2)->nullable();
            $table->decimal('this_phase', 10, 2)->nullable();
            $table->decimal('next_phase', 10, 2)->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('project_id')->on('projects')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_budgets');
    }
}
