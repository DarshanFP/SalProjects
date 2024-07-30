<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDpAccountDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('DP_AccountDetails', function (Blueprint $table) {
            $table->id(); // Adds an auto-incrementing primary key
            $table->string('account_detail_id')->unique(); // Unique account_detail_id
            $table->string('report_id'); // Ensure this matches the type in DP_Reports
            $table->string('particulars')->nullable();
            $table->decimal('amount_forwarded', 15, 2)->nullable()->default(0.00);
            $table->decimal('amount_sanctioned', 15, 2)->nullable()->default(0.00);
            $table->decimal('total_amount', 15, 2)->nullable()->default(0.00);
            $table->decimal('expenses_last_month', 15, 2)->nullable()->default(0.00);
            $table->decimal('expenses_this_month', 15, 2)->nullable()->default(0.00);
            $table->decimal('total_expenses', 15, 2)->nullable()->default(0.00);
            $table->decimal('balance_amount', 15, 2)->nullable()->default(0.00);
            $table->timestamps();

            $table->foreign('report_id')->references('report_id')->on('DP_Reports')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('DP_AccountDetails');
    }
}
