<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IES_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('IES_attachment_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->string('aadhar_card')->nullable();
            $table->string('fee_quotation')->nullable();
            $table->string('scholarship_proof')->nullable();
            $table->string('medical_confirmation')->nullable();
            $table->string('caste_certificate')->nullable();
            $table->string('self_declaration')->nullable();
            $table->string('death_certificate')->nullable();
            $table->string('request_letter')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IES_attachments');
    }
};
