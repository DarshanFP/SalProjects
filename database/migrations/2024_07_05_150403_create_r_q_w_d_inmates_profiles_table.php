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
            $table->foreignId('report_id')->constrained('rqwd_reports')->onDelete('cascade');
            $table->string('age_category')->nullable();
            $table->string('status')->nullable();
            $table->integer('number')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rqwd_inmates_profiles');
    }
}
