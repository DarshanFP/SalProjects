<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IIES_personal_info', function (Blueprint $table) {
            $table->id();
            $table->string('IIES_personal_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->string('iies_bname');
            $table->integer('iies_age')->nullable();
            $table->string('iies_gender')->nullable();
            $table->date('iies_dob')->nullable();
            $table->string('iies_email')->nullable();
            $table->string('iies_contact')->nullable();
            $table->string('iies_aadhar')->nullable();
            $table->text('iies_full_address')->nullable();
            $table->string('iies_father_name')->nullable();
            $table->string('iies_mother_name')->nullable();
            $table->string('iies_mother_tongue')->nullable();
            $table->string('iies_current_studies')->nullable();
            $table->string('iies_bcaste')->nullable();
            $table->string('iies_father_occupation')->nullable();
            $table->decimal('iies_father_income', 10, 2)->nullable();
            $table->string('iies_mother_occupation')->nullable();
            $table->decimal('iies_mother_income', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IIES_personal_info');
    }
};
