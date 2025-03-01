<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop the old project_ILP_revenue_goals table
        Schema::dropIfExists('project_ILP_revenue_goals');
    }

    public function down(): void
    {
        // Optionally recreate the table if needed during rollback
        Schema::create('project_ILP_revenue_goals', function (Blueprint $table) {
            $table->id();
            $table->string('ILP_revenue_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->json('business_plan_items')->nullable(); // Year-wise items
            $table->decimal('annual_income', 12, 2)->nullable();
            $table->decimal('annual_expenses', 12, 2)->nullable();
            $table->timestamps();
        });
    }
};
