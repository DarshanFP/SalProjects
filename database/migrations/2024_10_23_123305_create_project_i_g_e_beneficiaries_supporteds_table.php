<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IGE_beneficiaries_supported', function (Blueprint $table) {
            $table->id();
            $table->string('IGE_bnfcry_supprtd_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->string('class'); // Class of Beneficiaries
            $table->integer('total_number');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IGE_beneficiaries_supported');
    }
};
