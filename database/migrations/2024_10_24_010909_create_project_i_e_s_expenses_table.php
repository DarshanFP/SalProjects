<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IES_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('IES_expense_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->decimal('total_expenses', 10, 2);
            $table->decimal('expected_scholarship_govt', 10, 2)->nullable();
            $table->decimal('support_other_sources', 10, 2)->nullable();
            $table->decimal('beneficiary_contribution', 10, 2)->nullable();
            $table->decimal('balance_requested', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IES_expenses');
    }
};
