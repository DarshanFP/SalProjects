<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IES_family_working_members', function (Blueprint $table) {
            $table->id();
            $table->string('IES_family_member_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->string('member_name');
            $table->string('work_nature');
            $table->decimal('monthly_income', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IES_family_working_members');
    }
};
