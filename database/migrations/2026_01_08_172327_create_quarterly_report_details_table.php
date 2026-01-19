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
        Schema::create('quarterly_report_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quarterly_report_id');
            $table->string('particulars')->nullable();

            // Budget/Account details
            $table->decimal('opening_balance', 15, 2)->nullable()->default(0.00)->comment('Balance at start of quarter');
            $table->decimal('amount_forwarded', 15, 2)->nullable()->default(0.00);
            $table->decimal('amount_sanctioned', 15, 2)->nullable()->default(0.00);
            $table->decimal('total_amount', 15, 2)->nullable()->default(0.00);
            $table->decimal('total_expenses', 15, 2)->nullable()->default(0.00);
            $table->decimal('closing_balance', 15, 2)->nullable()->default(0.00)->comment('Balance at end of quarter');

            // Expenses breakdown by month (JSON: {month1: amount, month2: amount, month3: amount})
            $table->json('expenses_by_month')->nullable()->comment('Monthly breakdown of expenses within the quarter');

            $table->timestamps();

            // Foreign key
            $table->foreign('quarterly_report_id')
                  ->references('id')
                  ->on('quarterly_reports')
                  ->onDelete('cascade');

            // Indexes
            $table->index('quarterly_report_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quarterly_report_details');
    }
};
