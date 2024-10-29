<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IAH_budget_details', function (Blueprint $table) {
            $table->id();
            $table->string('IAH_budget_id')->unique();
            $table->string('project_id');
            $table->string('particular')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->decimal('total_expenses', 10, 2)->nullable();
            $table->decimal('family_contribution', 10, 2)->nullable();
            $table->decimal('amount_requested', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IAH_budget_details');
    }
};
