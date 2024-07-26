<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRqdpReportsTable extends Migration
{
    public function up()
    {
        Schema::create('rqdp_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained('oldDevelopmentProjects')->onDelete('cascade');
            $table->string('project_title')->nullable();
            $table->string('place')->nullable();
            $table->string('society_name')->nullable();
            $table->date('commencement_month_year')->nullable();
            $table->string('in_charge')->nullable();
            $table->integer('total_beneficiaries')->nullable();
            $table->string('reporting_period')->nullable(); // New field
            $table->date('reporting_period_from')->nullable(); //missing fields added
            $table->date('reporting_period_to')->nullable(); // missing fields added
            $table->text('goal')->nullable();
            $table->date('account_period_start')->nullable();
            $table->date('account_period_end')->nullable();
            $table->decimal('amount_sanctioned_overview', 15, 2)->nullable();
            $table->decimal('amount_forwarded_overview', 15, 2)->nullable();
            $table->decimal('amount_in_hand', 15, 2)->nullable();
            $table->decimal('total_balance_forwarded', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rqdp_reports');
    }
}
