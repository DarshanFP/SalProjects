<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRqisObjectivesTable extends Migration
{
    public function up()
    {
        Schema::create('rqis_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('rqis_reports')->onDelete('cascade');
            $table->text('objective')->nullable();
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

    public function down()
    {
        Schema::dropIfExists('rqis_objectives');
    }
}
