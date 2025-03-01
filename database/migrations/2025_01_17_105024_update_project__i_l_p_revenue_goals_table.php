<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ILP Revenue Business Plan Items Table
        Schema::create('project_ILP_revenue_plan_items', function (Blueprint $table) {
            $table->id();
            $table->string(column: 'ILP_revenue_plan_id')->unique(); // Unique identifier for each row
            $table->string('project_id'); // Foreign key to Project
            $table->string('item'); // Business plan item description
            $table->decimal('year_1', 12, 2)->nullable();
            $table->decimal('year_2', 12, 2)->nullable();
            $table->decimal('year_3', 12, 2)->nullable();
            $table->decimal('year_4', 12, 2)->nullable();
            $table->timestamps();
        });

        // ILP Revenue Income Table
        Schema::create('project_ILP_revenue_income', function (Blueprint $table) {
            $table->id();
            $table->string('ILP_revenue_income_id')->unique(); // Unique identifier for each row
            $table->string('project_id'); // Foreign key to Project
            $table->string('description'); // Income description
            $table->decimal('year_1', 12, 2)->nullable();
            $table->decimal('year_2', 12, 2)->nullable();
            $table->decimal('year_3', 12, 2)->nullable();
            $table->decimal('year_4', 12, 2)->nullable();
            $table->timestamps();
        });

        // ILP Revenue Expenses Table
        Schema::create('project_ILP_revenue_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('ILP_revenue_expenses_id')->unique(); // Unique identifier for each row
            $table->string('project_id'); // Foreign key to Project
            $table->string('description'); // Expense description
            $table->decimal('year_1', 12, 2)->nullable();
            $table->decimal('year_2', 12, 2)->nullable();
            $table->decimal('year_3', 12, 2)->nullable();
            $table->decimal('year_4', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_ILP_revenue_plan_items');
        Schema::dropIfExists('project_ILP_revenue_income');
        Schema::dropIfExists('project_ILP_revenue_expenses');
    }
};
