<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_RST_geographical_areas', function (Blueprint $table) {
            $table->id();
            $table->string('geographical_area_id')->unique();
            $table->string('project_id'); // Foreign Key to project
            $table->string('mandal')->nullable();
            $table->string('villages')->nullable();
            $table->string('town')->nullable();
            $table->integer('no_of_beneficiaries')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_RST_geographical_areas');
    }
};
