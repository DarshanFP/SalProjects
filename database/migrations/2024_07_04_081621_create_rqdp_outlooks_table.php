<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRqdpOutlooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rqdp_outlooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('rqdp_reports')->onDelete('cascade');
            $table->date('date')->nullable();
            $table->text('plan_next_month')->nullable();
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
        Schema::dropIfExists('rqdp_outlooks');
    }
}
