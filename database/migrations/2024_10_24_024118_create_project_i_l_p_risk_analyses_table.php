->nullable()<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_ILP_risk_analysis', function (Blueprint $table) {
            $table->id();
            $table->string('ILP_risk_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->text('identified_risks')->nullable();
            $table->text('mitigation_measures')->nullable();
            $table->text('business_sustainability')->nullable();
            $table->text('expected_profits')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_ILP_risk_analysis');
    }
};
