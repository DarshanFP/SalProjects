<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IIES_family_working_members', function (Blueprint $table) {
            $table->id();
            $table->string('IIES_family_member_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->string('iies_member_name');
            $table->string('iies_work_nature');
            $table->decimal('iies_monthly_income', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IIES_family_working_members');
    }
};
