<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_RST_target_group_annexure', function (Blueprint $table) {
            $table->id();
            $table->string('target_group_anxr_id')->unique();
            $table->string('project_id'); // Foreign Key to project
            $table->string('rst_name')->nullable();
            $table->string('rst_religion')->nullable();
            $table->string('rst_caste')->nullable();
            $table->string('rst_education_background')->nullable();
            $table->text('rst_family_situation')->nullable();
            $table->text('rst_paragraph')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_RST_target_group_annexure');
    }
};
