<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_CCI_annexed_target_group', function (Blueprint $table) {
            $table->id();
            $table->string('CCI_target_group_id')->unique();
            $table->string('project_id');
            $table->string('beneficiary_name');
            $table->date('dob');
            $table->date('date_of_joining');
            $table->string('class_of_study');
            $table->text('family_background_description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_CCI_annexed_target_group');
    }
};
