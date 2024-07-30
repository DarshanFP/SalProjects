<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDpOutlooksTable extends Migration
{
    public function up()
    {
        Schema::create('DP_Outlooks', function (Blueprint $table) {
            $table->id(); // Adds an auto-incrementing primary key
            $table->string('outlook_id')->unique(); // Unique outlook_id
            $table->string('report_id'); // Ensure this matches the type in DP_Reports
            $table->date('date')->nullable();
            $table->text('plan_next_month')->nullable();
            $table->timestamps();

            $table->foreign('report_id')->references('report_id')->on('DP_Reports')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('DP_Outlooks');
    }
}
