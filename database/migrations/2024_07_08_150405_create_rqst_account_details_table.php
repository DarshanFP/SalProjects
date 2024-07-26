<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRqstAccountDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('rqst_account_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('rqst_reports')->onDelete('cascade');
            $table->text('particulars')->nullable();
            $table->decimal('amount_forwarded', 15, 2)->nullable();
            $table->decimal('amount_sanctioned', 15, 2)->nullable();
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->decimal('expenses_last_month', 15, 2)->nullable();
            $table->decimal('expenses_this_month', 15, 2)->nullable();
            $table->decimal('total_expenses', 15, 2)->nullable();
            $table->decimal('balance_amount', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rqst_account_details');
    }
}
