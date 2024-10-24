<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_ILP_personal_info', function (Blueprint $table) {
            $table->id();
            $table->string('ILP_personal_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->string('name');
            $table->integer('age');
            $table->string('gender');
            $table->date('dob'); // Date of birth
            $table->string('email')->nullable();
            $table->string('contact_no');
            $table->string('aadhar_id')->nullable();
            $table->text('address');
            $table->string('occupation');
            $table->string('marital_status');
            $table->string('spouse_name')->nullable();
            $table->integer('children_no')->nullable();
            $table->text('children_edu')->nullable();
            $table->string('religion');
            $table->string('caste');
            $table->text('family_situation');
            $table->boolean('small_business_status')->default(false);
            $table->text('small_business_details')->nullable();
            $table->decimal('monthly_income', 10, 2);
            $table->text('business_plan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_ILP_personal_info');
    }
};
