<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('rqdl_outlooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('rqdl_reports')->onDelete('cascade');
            $table->date('date')->nullable();
            $table->text('plan_next_month')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('rqdl_outlooks');
    }
};
