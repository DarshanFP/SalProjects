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
        Schema::create('activity_histories', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['project', 'report'])->index();
            $table->string('related_id')->index(); // project_id or report_id
            $table->string('previous_status')->nullable();
            $table->string('new_status')->index();
            $table->unsignedBigInteger('changed_by_user_id');
            $table->string('changed_by_user_role', 50);
            $table->string('changed_by_user_name', 255);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign key relationship
            $table->foreign('changed_by_user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Composite index for type + related_id (for efficient queries)
            $table->index(['type', 'related_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_histories');
    }
};
