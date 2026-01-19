<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates a pivot table to support many-to-many relationship between users and provinces.
     * This allows general users to be provincial for multiple provinces.
     *
     * For regular provincial users (role='provincial'), we still use province_id.
     * For general users (role='general'), we use this pivot table to allow multiple province assignments.
     */
    public function up(): void
    {
        Schema::create('provincial_user_province', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('province_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('province_id')
                ->references('id')
                ->on('provinces')
                ->onDelete('cascade');

            // Unique constraint: a user can only be assigned once to a province
            $table->unique(['user_id', 'province_id']);

            // Indexes for performance
            $table->index('user_id');
            $table->index('province_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provincial_user_province');
    }
};
