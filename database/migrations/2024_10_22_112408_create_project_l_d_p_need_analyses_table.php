<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_LDP_need_analysis', function (Blueprint $table) {
            $table->id();
            $table->string('LDP_need_analysis_id')->unique();
            $table->string('project_id'); // Foreign key to project

            // Store file path or name of the uploaded document
            $table->string('document_path');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_LDP_need_analysis');
    }
};
