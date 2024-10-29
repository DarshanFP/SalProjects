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
            $table->string('project_id')->nullable();
            $table->string('name')->nullable();
            $table->integer('age')->nullable();
            $table->enum('gender', ['Female', 'Male', 'Transgender'])->nullable();
            $table->date('dob')->nullable();
            $table->string('aadhar', 12)->nullable();
            $table->string('contact')->nullable();
            $table->text('address')->nullable();
            $table->string('email')->nullable();
            $table->string('guardian_name')->nullable();
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
