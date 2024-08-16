<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRqwdInmatesProfilesTable extends Migration
{
    public function up()
    {
        Schema::create('rqwd_inmates_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('report_id');
            $table->string('age_category')->nullable();
            $table->string('status')->nullable();
            $table->integer('number')->nullable();
            $table->integer('total')->nullable();
            $table->timestamps();

            $table->foreign('report_id')->references('report_id')->on('DP_Reports')->onDelete('cascade');

        });
    }

    public function down()
    {
        Schema::dropIfExists('rqwd_inmates_profiles');
    }
}
