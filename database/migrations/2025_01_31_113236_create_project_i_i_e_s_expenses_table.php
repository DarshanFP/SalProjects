<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration { 
    public function up(): void
    {
        Schema::create('project_IIES_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('IIES_expense_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->decimal('iies_total_expenses', 10, 2)->default(0);
            $table->decimal('iies_expected_scholarship_govt', 10, 2)->default(0);
            $table->decimal('iies_support_other_sources', 10, 2)->default(0);
            $table->decimal('iies_beneficiary_contribution', 10, 2)->default(0);
            $table->decimal('iies_balance_requested', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('project_IIES_expense_details', function (Blueprint $table) {
            $table->id();
            $table->string('IIES_expense_id'); // Foreign key to ProjectIIESExpenses
            $table->string('iies_particular');
            $table->decimal('iies_amount', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IIES_expense_details');
        Schema::dropIfExists('project_IIES_expenses');
    }
};
