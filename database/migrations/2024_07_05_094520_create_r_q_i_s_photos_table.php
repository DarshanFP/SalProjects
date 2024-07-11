<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRqisPhotosTable extends Migration
{
    public function up()
    {
        Schema::create('rqis_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('rqis_reports')->onDelete('cascade');
            $table->string('photo_path')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rqis_photos');
    }
}
