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
            $table->string('name')->nullable();
            $table->string('religion')->nullable();
            $table->string('caste')->nullable();
            $table->string('education_background')->nullable();
            $table->string('family_situation')->nullable();
            $table->text('paragraph')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_RST_target_group_annexure');
    }
};
