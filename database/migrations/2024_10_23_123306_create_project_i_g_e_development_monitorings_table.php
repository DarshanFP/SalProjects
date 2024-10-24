<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IGE_development_monitoring', function (Blueprint $table) {
            $table->id();
            $table->string('IGE_dvlpmnt_mntrng_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->text('proposed_activities')->nullable();
            $table->text('monitoring_methods')->nullable();
            $table->text('evaluation_process')->nullable();
            $table->text('conclusion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IGE_development_monitoring');
    }
};
