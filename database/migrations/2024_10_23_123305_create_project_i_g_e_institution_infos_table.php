<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IGE_institution_info', function (Blueprint $table) {
            $table->id();
            $table->string('IGE_institution_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->string('institutional_type'); // Institutional / Non-Institutional
            $table->string('age_group'); // Children / Youth
            $table->integer('previous_year_beneficiaries')->nullable();
            $table->text('outcome_impact')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IGE_institution_info');
    }
};
