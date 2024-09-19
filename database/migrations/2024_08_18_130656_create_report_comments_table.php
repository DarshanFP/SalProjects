<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportCommentsTable extends Migration
{
    public function up()
    {
        Schema::create('report_comments', function (Blueprint $table) {
            $table->id();
            $table->string('R_comment_id')->unique();
            $table->string('report_id');
            $table->foreignId('user_id')->constrained('users');
            $table->text('comment');
            $table->timestamps();

            $table->foreign('report_id')->references('report_id')->on('DP_Reports')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('report_comments');
    }
}
