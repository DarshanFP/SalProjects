<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_RST_institution_info', function (Blueprint $table) {
            $table->id();
            $table->string('RST_institution_id')->unique();
            $table->string('project_id'); // Foreign Key to project
            $table->year('year_setup');   // Year the training center was set up
            $table->integer('total_students_trained')->nullable();
            $table->integer('beneficiaries_last_year')->nullable();
            $table->text('training_outcome')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_RST_institution_info');
    }
};
