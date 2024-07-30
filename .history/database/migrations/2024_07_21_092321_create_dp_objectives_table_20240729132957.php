<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDpObjectivesTable extends Migration
{
    public function up()
    {
        Schema::create('DP_Objectives', function (Blueprint $table) {
            $table->id(); // Adds an auto-incrementing primary key
            $table->string('objective_id')->unique(); // Unique objective_id
            $table->string('report_id'); // Ensure this matches the type in DP_Reports
            $table->text('objective')->nullable();
            $table->text('expected_outcome')->nullable();
            $table->text('not_happened')->nullable();
            $table->text('why_not_happened')->nullable();
            $table->boolean('changes')->nullable();
            $table->text('why_changes')->nullable();
            $table->text('lessons_learnt')->nullable();
            $table->text('todo_lessons_learnt')->nullable();
            $table->timestamps();

            $table->foreign('report_id')->references('report_id')->on('DP_Reports')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('DP_Objectives');
    }
}
