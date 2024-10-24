<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IES_educational_background', function (Blueprint $table) {
            $table->id();
            $table->string('IES_education_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->string('previous_class');
            $table->decimal('amount_sanctioned', 10, 2)->nullable();
            $table->decimal('amount_utilized', 10, 2)->nullable();
            $table->decimal('scholarship_previous_year', 10, 2)->nullable();
            $table->text('academic_performance')->nullable();
            $table->string('present_class');
            $table->decimal('expected_scholarship', 10, 2)->nullable();
            $table->decimal('family_contribution', 10, 2)->nullable();
            $table->text('reason_no_support')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IES_educational_background');
    }
};
