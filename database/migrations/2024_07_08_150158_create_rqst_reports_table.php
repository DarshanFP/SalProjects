<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRqstReportsTable extends Migration
{
    public function up()
    {
        Schema::create('rqst_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('project_title')->nullable();
            $table->string('place')->nullable();
            $table->string('society_name')->nullable();
            $table->string('commencement_month_year')->nullable();
            $table->string('in_charge')->nullable();
            $table->integer('total_beneficiaries')->nullable();
            $table->string('reporting_period')->nullable();
            $table->text('goal')->nullable();
            $table->date('account_period_start')->nullable();
            $table->date('account_period_end')->nullable();
            $table->decimal('prjct_amount_sanctioned', 15, 2)->nullable();
            $table->decimal('l_y_amount_forwarded', 15, 2)->nullable();
            $table->decimal('amount_in_hand', 15, 2)->nullable();
            $table->decimal('total_balance_forwarded', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rqst_reports');
    }
}
