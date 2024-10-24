<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_CCI_achievements', function (Blueprint $table) {
            $table->id();
            $table->string('CCI_achievements_id')->unique();
            $table->string('project_id'); // Foreign key to project

            // Achievements
            $table->text('academic_achievements')->nullable();
            $table->text('sport_achievements')->nullable();
            $table->text('other_achievements')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_CCI_achievements');
    }
};
