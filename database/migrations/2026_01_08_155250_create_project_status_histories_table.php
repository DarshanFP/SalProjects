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
        Schema::create('project_status_histories', function (Blueprint $table) {
            $table->id();
            $table->string('project_id');
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->unsignedBigInteger('changed_by_user_id');
            $table->string('changed_by_user_role')->nullable();
            $table->string('changed_by_user_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign key relationship
            $table->foreign('project_id')
                  ->references('project_id')
                  ->on('projects')
                  ->onDelete('cascade');

            $table->foreign('changed_by_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Indexes for better query performance
            $table->index('project_id');
            $table->index('changed_by_user_id');
            $table->index('new_status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_status_histories');
    }
};
