<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IAH_documents', function (Blueprint $table) {
            $table->id();
            $table->string('IAH_doc_id')->unique();
            $table->string('project_id');
            $table->string('aadhar_copy')->nullable();
            $table->string('request_letter')->nullable();
            $table->string('medical_reports')->nullable();
            $table->string('other_docs')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IAH_documents');
    }
};
