<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRqisReportsTable extends Migration
{
    public function up()
    {
        Schema::create('rqis_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('project_title')->nullable();
            $table->string('place')->nullable();
            $table->string('society_name')->nullable();  // Add this field
            $table->string('commencement_month_year')->nullable();  // Add this field
            $table->string('province')->nullable();
            $table->string('in_charge')->nullable();
            $table->integer('total_beneficiaries')->nullable();  // Add this field
            $table->string('institution_type')->nullable();
            $table->text('beneficiary_statistics')->nullable();
            $table->string('monitoring_period')->nullable();
            $table->text('goal')->nullable();
            $table->date('account_period_start')->nullable();
            $table->date('account_period_end')->nullable();
            $table->decimal('amount_sanctioned_overview', 15, 2)->nullable();
            $table->decimal('amount_forwarded_overview', 15, 2)->nullable();
            $table->decimal('total_balance_forwarded', 15, 2)->nullable();
            $table->decimal('amount_in_hand', 15, 2)->nullable(); // New field for total of amount_in_hand

            // Age profile totals
            $table->integer('total_up_to_previous_below_5')->nullable();
            $table->integer('total_present_academic_below_5')->nullable();
            $table->integer('total_up_to_previous_6_10')->nullable();
            $table->integer('total_present_academic_6_10')->nullable();
            $table->integer('total_up_to_previous_11_15')->nullable();
            $table->integer('total_present_academic_11_15')->nullable();
            $table->integer('total_up_to_previous_16_above')->nullable();
            $table->integer('total_present_academic_16_above')->nullable();
            $table->integer('grand_total_up_to_previous')->nullable();
            $table->integer('grand_total_present_academic')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rqis_reports');
    }
}
