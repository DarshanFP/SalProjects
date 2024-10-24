<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IGE_budget', function (Blueprint $table) {
            $table->id();
            $table->string('IGE_budget_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->string('name');
            $table->string('study_proposed');
            $table->decimal('college_fees', 10, 2)->nullable();
            $table->decimal('hostel_fees', 10, 2)->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->decimal('scholarship_eligibility', 10, 2)->nullable();
            $table->decimal('family_contribution', 10, 2)->nullable();
            $table->decimal('amount_requested', 10, 2)->nullable();
            $table->decimal('total_amount_requested', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IGE_budget');
    }
};
