<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportAttachmentsTable extends Migration
{
    public function up()
    {
        Schema::create('report_attachments', function (Blueprint $table) {
            $table->id(); // Adds an auto-incrementing primary key
            $table->string('attachment_id')->unique(); // Unique attachment_id
            $table->string('report_id'); // Ensure this matches the type in DP_Reports
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->text('description')->nullable();
            $table->string('public_url')->nullable();
            $table->timestamps();

            // Add foreign key constraint
            $table->foreign('report_id')->references('report_id')->on('DP_Reports')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('report_attachments');
    }
}
