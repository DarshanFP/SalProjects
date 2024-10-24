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
            $table->string('name');
            $table->integer('age');
            $table->string('gender');
            $table->date('dob');
            $table->string('email');
            $table->string('contact');
            $table->string('aadhar');
            $table->text('full_address');
            $table->string('father_name');
            $table->string('mother_name');
            $table->string('mother_tongue');
            $table->string('current_studies');
            $table->string('caste');
            // New fields for Family Information
            $table->string('father_occupation');
            $table->decimal('father_income', 10, 2)->nullable();
            $table->string('mother_occupation');
            $table->decimal('mother_income', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IES_personal_info');
    }
};
