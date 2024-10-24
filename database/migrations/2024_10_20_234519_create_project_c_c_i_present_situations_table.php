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
        Schema::create('project_CCI_present_situation', function (Blueprint $table) {
            $table->id();
            $table->string('CCI_present_situation_id')->unique();
            $table->string('project_id'); // Foreign key to project

            // Internal and external challenges
            $table->text('internal_challenges')->nullable();
            $table->text('external_challenges')->nullable();

            // Area of focus for the current year
            $table->text('area_of_focus')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_CCI_present_situation');
    }
};
