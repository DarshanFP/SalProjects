<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRqisAgeProfilesTable extends Migration
{
    public function up()
    {
        Schema::create('rqis_age_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('report_id');
            $table->string('age_group')->nullable();
            $table->string('education')->nullable();
            $table->integer('up_to_previous_year')->nullable(); // Changed to integer
            $table->integer('present_academic_year')->nullable(); // Changed to integer
            $table->timestamps();

            $table->foreign('report_id')->references('report_id')->on('DP_Reports')->onDelete('cascade');

        });
    }
//
    public function down()
    {
        Schema::dropIfExists('rqis_age_profiles');
    }
}
