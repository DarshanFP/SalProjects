<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_RST_target_group', function (Blueprint $table) {
            $table->id();
            $table->string('RST_target_group_id')->unique();
            $table->string('project_id'); // Foreign Key to project
            $table->integer('tg_no_of_beneficiaries')->nullable();
            $table->text('beneficiaries_description_problems')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_RST_target_group');
    }
};
