<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IAH_personal_info', function (Blueprint $table) {
            $table->id();
            $table->string('IAH_info_id')->unique();
            $table->string('project_id');
            $table->string('name');
            $table->integer('age');
            $table->enum('gender', ['Female', 'Male', 'Transgender']);
            $table->date('dob');
            $table->string('aadhar', 12);
            $table->string('contact');
            $table->text('address');
            $table->string('email');
            $table->string('guardian_name');
            $table->integer('children')->nullable();
            $table->string('caste')->nullable();
            $table->string('religion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IAH_personal_info');
    }
};
