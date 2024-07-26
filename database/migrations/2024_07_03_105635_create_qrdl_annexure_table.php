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
            $table->foreignId('report_id')->constrained('rqdl_reports')->onDelete('cascade');
            $table->string('beneficiary_name')->nullable();
            $table->date('support_date')->nullable();
            $table->text('self_employment')->nullable();
            $table->decimal('amount_sanctioned', 10, 2)->nullable();
            $table->decimal('monthly_profit', 10, 2)->nullable();
            $table->decimal('annual_profit', 10, 2)->nullable();
            $table->text('impact')->nullable();
            $table->text('challenges')->nullable();
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
