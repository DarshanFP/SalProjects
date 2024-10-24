<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_ILP_strength_weakness', function (Blueprint $table) {
            $table->id();
            $table->string('ILP_strength_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->text('strengths');
            $table->text('weaknesses');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_ILP_strength_weakness');
    }
};
