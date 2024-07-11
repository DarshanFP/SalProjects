<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRqstOutlooksTable extends Migration
{
    public function up()
    {
        Schema::create('rqst_outlooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('rqst_reports')->onDelete('cascade');
            $table->date('date')->nullable();
            $table->text('plan_next_month')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rqst_outlooks');
    }
}
