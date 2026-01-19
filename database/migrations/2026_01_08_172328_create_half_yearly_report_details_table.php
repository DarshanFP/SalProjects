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
        Schema::create('half_yearly_report_details', function (Blueprint $table) {
            $table->id();
            $table->string('half_yearly_report_id');
            $table->string('particulars')->nullable();

            // Budget/Account details
            $table->decimal('opening_balance', 15, 2)->nullable()->default(0.00)->comment('Balance at start of half-year');
            $table->decimal('amount_forwarded', 15, 2)->nullable()->default(0.00);
            $table->decimal('amount_sanctioned', 15, 2)->nullable()->default(0.00);
            $table->decimal('total_amount', 15, 2)->nullable()->default(0.00);
            $table->decimal('total_expenses', 15, 2)->nullable()->default(0.00);
            $table->decimal('closing_balance', 15, 2)->nullable()->default(0.00)->comment('Balance at end of half-year');

            // Expenses breakdown by quarter (JSON: {q1: amount, q2: amount} or {q3: amount, q4: amount})
            $table->json('expenses_by_quarter')->nullable()->comment('Quarterly breakdown of expenses within the half-year');

            $table->timestamps();

            // Foreign key - reference report_id (string) not id
            // Note: Using string foreign key, ensure report_id column exists and is indexed
            $table->foreign('half_yearly_report_id')
                  ->references('report_id')
                  ->on('half_yearly_reports')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            // Indexes
            $table->index('half_yearly_report_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('half_yearly_report_details');
    }
};
