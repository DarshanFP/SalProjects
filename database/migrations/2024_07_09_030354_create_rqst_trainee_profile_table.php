<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rqst_trainee_profile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('rqst_reports')->onDelete('cascade');
            $table->text('education_category')->nullable();
            $table->integer('number')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rqst_trainee_profile');
    }
};
