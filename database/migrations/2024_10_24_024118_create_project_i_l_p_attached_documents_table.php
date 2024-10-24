<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_ILP_attached_docs', function (Blueprint $table) {
            $table->id();
            $table->string('ILP_doc_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->string('aadhar_doc')->nullable(); // File path for Aadhar document
            $table->string('request_letter_doc')->nullable(); // File path for request letter
            $table->string('purchase_quotation_doc')->nullable(); // File path for purchase quotations
            $table->string('other_doc')->nullable(); // File path for any other documents
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_ILP_attached_docs');
    }
};
