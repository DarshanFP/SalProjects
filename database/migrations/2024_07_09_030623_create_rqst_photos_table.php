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
        Schema::create('rqst_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('rqst_reports')->onDelete('cascade');
            $table->string('photo_path')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rqst_photos');
    }
};
