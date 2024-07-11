<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRqdpActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rqdp_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objective_id')->constrained('rqdp_objectives')->onDelete('cascade');
            $table->string('month')->nullable();
            $table->text('summary_activities')->nullable();
            $table->text('qualitative_quantitative_data')->nullable();
            $table->text('intermediate_outcomes')->nullable();
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
        Schema::dropIfExists('rqdp_activities');
    }
}
