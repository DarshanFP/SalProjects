<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRqisAccountDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('rqis_account_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('rqis_reports')->onDelete('cascade');
            $table->string('particulars')->nullable();
        $table->decimal('amount_forwarded', 15, 2)->default(0);
        $table->decimal('amount_sanctioned', 15, 2)->default(0);
        $table->decimal('total_amount', 15, 2)->default(0);
        $table->decimal('expenses_last_month', 15, 2)->default(0);
        $table->decimal('expenses_this_month', 15, 2)->default(0);
        $table->decimal('total_expenses', 15, 2)->default(0);
        $table->decimal('balance_amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rqis_account_details');
    }
}
