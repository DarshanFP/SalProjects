<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_LDP_target_group', function (Blueprint $table) {
            $table->id();
            $table->string('LDP_target_group_id')->unique();
            $table->string('project_id'); // Foreign key to project

            // Fields for target group
            $table->string('L_beneficiary_name')->nullable();
            $table->text('L_family_situation')->nullable();
            $table->text('L_nature_of_livelihood')->nullable();
            $table->integer('L_amount_requested')->nullable(); // Using integer instead of decimal

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_LDP_target_group');
    }
};
