<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_ILP_budget', function (Blueprint $table) {
            $table->id();
            $table->string('ILP_budget_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->text('budget_desc')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->decimal('beneficiary_contribution', 12, 2)->nullable();
            $table->decimal('amount_requested', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_ILP_budget');
    }
};
