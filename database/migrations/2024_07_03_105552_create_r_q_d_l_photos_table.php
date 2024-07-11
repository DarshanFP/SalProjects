<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRQDLPhotosTable extends Migration
{
    public function up()
    {
        Schema::create('rqdl_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('rqdl_reports')->onDelete('cascade');
            $table->string('path')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rqdl_photos');
    }
}
