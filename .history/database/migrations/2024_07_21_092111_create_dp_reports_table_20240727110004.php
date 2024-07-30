<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDpReportsTable extends Migration
{
    public function up()
    {
        Schema::create('DP_Reports', function (Blueprint $table) {
            $table->id(); // Adds an auto-incrementing primary key
            $table->string('report_id')->unique(); // Unique report_id
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('cascade');
            $table->string('project_title')->nullable();
            $table->string('place')->nullable();
            $table->string('society_name')->nullable();
            $table->date('commencement_month_year')->nullable();
            $table->string('in_charge')->nullable();
            $table->integer('total_beneficiaries')->nullable();
            $table->date('report_month_year')->nullable();
            $table->string('report_before_id')->nullable();
            $table->text('goal')->nullable();
            $table->date('account_period_start')->nullable();
            $table->date('account_period_end')->nullable();
            $table->decimal('amount_sanctioned_overview', 15, 2)->nullable();
            $table->decimal('amount_forwarded_overview', 15, 2)->nullable();
            $table->decimal('amount_in_hand', 15, 2)->nullable();
            $table->decimal('total_balance_forwarded', 15, 2)->nullable();
            $table->string('status')->default('underwriting'); // Added the status column
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('DP_Reports');
    }
}
