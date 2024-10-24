<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectEduRUTTargetGroupsTable extends Migration
{
    public function up()
    {
        Schema::create('project_edu_rut_target_groups', function (Blueprint $table) {
            $table->id();
            $table->string('target_group_id')->unique();
            $table->string('project_id');
            $table->string('beneficiary_name')->nullable();
            $table->string('caste')->nullable();
            $table->string('institution_name')->nullable();
            $table->string('class_standard')->nullable();  // Class or standard
            $table->decimal('total_tuition_fee', 10, 2)->nullable();  // Tuition fee amount
            $table->boolean('eligibility_scholarship')->nullable();  // Eligibility for scholarship
            $table->decimal('expected_amount', 10, 2)->nullable();  // Expected scholarship amount
            $table->decimal('contribution_from_family', 10, 2)->nullable();  // Family's contribution
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('project_id')->references('project_id')->on('projects')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_edu_rut_target_groups');
    }
}
