<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQrdlAnnexureTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('qrdl_annexure', function (Blueprint $table) {
            $table->id();
            $table->string('report_id');
            $table->foreign('report_id')->references('report_id')->on('DP_Reports')->onDelete('cascade');
            $table->string('dla_beneficiary_name')->nullable();
            $table->date('dla_support_date')->nullable();
            $table->text('dla_self_employment')->nullable();
            $table->decimal('dla_amount_sanctioned', 10, 2)->nullable();
            $table->decimal('dla_monthly_profit', 10, 2)->nullable();
            $table->decimal('dla_annual_profit', 10, 2)->nullable();
            $table->text('dla_impact')->nullable();
            $table->text('dla_challenges')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qrdl_annexure');
    }
}
