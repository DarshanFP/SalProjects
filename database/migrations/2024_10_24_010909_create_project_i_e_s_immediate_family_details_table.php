<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IES_immediate_family_details', function (Blueprint $table) {
            $table->id();
            $table->string('IES_family_detail_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->boolean('mother_expired')->default(false);
            $table->boolean('father_expired')->default(false);
            $table->boolean('grandmother_support')->default(false);
            $table->boolean('grandfather_support')->default(false);
            $table->boolean('father_deserted')->default(false);
            $table->boolean('father_sick')->default(false);
            $table->boolean('father_hiv_aids')->default(false);
            $table->boolean('father_disabled')->default(false);
            $table->boolean('father_alcoholic')->default(false);
            $table->string('father_health_others')->nullable();
            $table->boolean('mother_sick')->default(false);
            $table->boolean('mother_hiv_aids')->default(false);
            $table->boolean('mother_disabled')->default(false);
            $table->boolean('mother_alcoholic')->default(false);
            $table->string('mother_health_others')->nullable();
            $table->boolean('own_house')->default(false);
            $table->boolean('rented_house')->default(false);
            $table->string('residential_others')->nullable();
            $table->text('family_situation')->nullable();
            $table->text('assistance_need')->nullable();
            $table->boolean('received_support')->default(false);
            $table->text('support_details')->nullable();
            $table->boolean('employed_with_stanns')->default(false);
            $table->text('employment_details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IES_immediate_family_details');
    }
};
