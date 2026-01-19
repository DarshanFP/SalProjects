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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->boolean('email_notifications')->default(true);
            $table->boolean('in_app_notifications')->default(true);
            $table->string('notification_frequency')->default('immediate'); // immediate, daily, weekly
            $table->boolean('status_change_notifications')->default(true);
            $table->boolean('report_submission_notifications')->default(true);
            $table->boolean('approval_notifications')->default(true);
            $table->boolean('rejection_notifications')->default(true);
            $table->boolean('deadline_reminder_notifications')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
