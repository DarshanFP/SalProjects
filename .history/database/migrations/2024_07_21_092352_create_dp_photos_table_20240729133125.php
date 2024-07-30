<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDpPhotosTable extends Migration
{
    public function up()
    {
        Schema::create('DP_Photos', function (Blueprint $table) {
            $table->id(); // Adds an auto-incrementing primary key
            $table->string('photo_id')->unique(); // Unique photo_id
            $table->string('report_id'); // Ensure this matches the type in DP_Reports
            $table->string('photo_path')->nullable();
            $table->string('photo_name')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('report_id')->references('report_id')->on('DP_Reports')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('DP_Photos');
    }
}
