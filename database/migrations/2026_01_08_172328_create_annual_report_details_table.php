<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('annual_report_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('annual_report_id');
            $table->string('particulars')->nullable();

            // Budget/Account details
            $table->decimal('opening_balance', 15, 2)->nullable()->default(0.00)->comment('Balance at start of year');
            $table->decimal('amount_forwarded', 15, 2)->nullable()->default(0.00);
            $table->decimal('amount_sanctioned', 15, 2)->nullable()->default(0.00);
            $table->decimal('total_amount', 15, 2)->nullable()->default(0.00);
            $table->decimal('total_expenses', 15, 2)->nullable()->default(0.00);
            $table->decimal('closing_balance', 15, 2)->nullable()->default(0.00)->comment('Balance at end of year');

            // Expenses breakdown by half-year (JSON: {h1: amount, h2: amount})
            $table->json('expenses_by_half_year')->nullable()->comment('Half-yearly breakdown of expenses');
            // Expenses breakdown by quarter (JSON: {q1: amount, q2: amount, q3: amount, q4: amount})
            $table->json('expenses_by_quarter')->nullable()->comment('Quarterly breakdown of expenses');

            $table->timestamps();

            // Foreign key
            $table->foreign('annual_report_id')
                  ->references('id')
                  ->on('annual_reports')
                  ->onDelete('cascade');

            // Indexes
            $table->index('annual_report_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annual_report_details');
    }
};
