<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOldDevelopmentProjectsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('oldDevelopmentProjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('project_title');
            $table->string('place');
            $table->string('society_name');
            $table->string('commencement_month_year'); // Combined month and year
            $table->string('in_charge');
            $table->integer('total_beneficiaries');
            $table->string('reporting_period');
            $table->text('goal');
            $table->decimal('total_amount_sanctioned', 10, 2)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oldDevelopmentProjects');
    }
}
