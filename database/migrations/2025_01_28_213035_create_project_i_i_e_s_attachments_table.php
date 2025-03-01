<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IIES_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('IIES_attachment_id')->unique();
            $table->string('project_id'); // This references your Projects table (e.g. 'projects.project_id')
            $table->string('iies_aadhar_card')->nullable();
            $table->string('iies_fee_quotation')->nullable();
            $table->string('iies_scholarship_proof')->nullable();
            $table->string('iies_medical_confirmation')->nullable();
            $table->string('iies_caste_certificate')->nullable();
            $table->string('iies_self_declaration')->nullable();
            $table->string('iies_death_certificate')->nullable();
            $table->string('iies_request_letter')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IIES_attachments');
    }
};
