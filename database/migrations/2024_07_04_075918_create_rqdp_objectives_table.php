<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRqdpObjectivesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rqdp_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('rqdp_reports')->onDelete('cascade');
            $table->text('expected_outcome')->nullable();
            $table->text('not_happened')->nullable();
            $table->text('why_not_happened')->nullable();
            $table->boolean('changes')->nullable();
            $table->text('why_changes')->nullable();
            $table->text('lessons_learnt')->nullable();
            $table->text('todo_lessons_learnt')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rqdp_objectives');
    }
}
