<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IGE_ongoing_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->string('IGE_ongoing_bnfcry_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->string('obeneficiary_name')->nullable();
            $table->string('ocaste')->nullable();
            $table->text('oaddress')->nullable();
            $table->string('ocurrent_group_year_of_study')->nullable();
            $table->text('operformance_details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IGE_ongoing_beneficiaries');
    }
};
