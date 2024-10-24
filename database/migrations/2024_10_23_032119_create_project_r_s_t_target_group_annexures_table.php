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
            $table->string('name');
            $table->string('religion');
            $table->string('caste');
            $table->string('education_background');
            $table->string('family_situation');
            $table->text('paragraph');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_RST_target_group_annexure');
    }
};
