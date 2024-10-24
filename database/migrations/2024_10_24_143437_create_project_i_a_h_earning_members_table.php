<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IAH_earning_members', function (Blueprint $table) {
            $table->id();
            $table->string('IAH_earning_id')->unique();
            $table->string('project_id');
            $table->string('member_name');
            $table->string('work_type');
            $table->decimal('monthly_income', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IAH_earning_members');
    }
};
