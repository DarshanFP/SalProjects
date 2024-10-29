<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IGE_new_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->string('IGE_new_beneficiaries_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->string('beneficiary_name')->nullable();
            $table->string('caste')->nullable();
            $table->text('address')->nullable();
            $table->string('group_year_of_study')->nullable();
            $table->text('family_background_need')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IGE_new_beneficiaries');
    }
};
