<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IAH_health_condition', function (Blueprint $table) {
            $table->id();
            $table->string('IAH_health_id')->unique();
            $table->string('project_id');
            $table->string('illness');
            $table->boolean('treatment');
            $table->string('doctor')->nullable();
            $table->string('hospital')->nullable();
            $table->text('doctor_address')->nullable();
            $table->text('health_situation');
            $table->text('family_situation')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IAH_health_condition');
    }
};
