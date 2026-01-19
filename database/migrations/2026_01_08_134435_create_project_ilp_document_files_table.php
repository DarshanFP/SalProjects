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
        Schema::create('project_ILP_document_files', function (Blueprint $table) {
            $table->id();
            $table->string('ILP_doc_id'); // Foreign key to project_ILP_attached_docs
            $table->string('project_id'); // Foreign key to projects
            $table->string('field_name'); // 'aadhar_doc', 'request_letter_doc', etc.
            $table->string('file_path'); // Storage path
            $table->string('file_name'); // User-provided name or generated name
            $table->text('description')->nullable();
            $table->string('serial_number', 2)->default('01'); // 01, 02, 03, etc.
            $table->string('public_url')->nullable();
            $table->timestamps();

            // Indexes for better query performance
            $table->index(['project_id', 'field_name']);
            $table->index('ILP_doc_id');
            
            // Foreign key constraints
            $table->foreign('project_id')->references('project_id')->on('projects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_ILP_document_files');
    }
};
