<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IES_personal_info', function (Blueprint $table) {
            $table->id();
            $table->string('IES_personal_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->string('name')->nullable();
            $table->integer('age')->nullable();
            $table->string('gender')->nullable();
            $table->date('dob')->nullable();
            $table->string('email')->nullable();
            $table->string('contact');
            $table->string('aadhar')->nullable();
            $table->text('full_address')->nullable();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('mother_tongue')->nullable();
            $table->string('current_studies')->nullable();
            $table->string('caste')->nullable();
            // New fields for Family Information
            $table->string('father_occupation')->nullable();
            $table->decimal('father_income', 10, 2)->nullable();
            $table->string('mother_occupation')->nullable();
            $table->decimal('mother_income', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IES_personal_info');
    }
};
