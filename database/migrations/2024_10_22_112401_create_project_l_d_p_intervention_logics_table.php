<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_LDP_intervention_logic', function (Blueprint $table) {
            $table->id();
            $table->string('LDP_intervention_logic_id')->unique();
            $table->string('project_id'); // Foreign key to project

            // Fields for intervention logic
            $table->text('intervention_logic')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_LDP_intervention_logic');
    }
};
