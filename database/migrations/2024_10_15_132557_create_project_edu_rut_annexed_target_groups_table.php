<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectEduRUTAnnexedTargetGroupsTable extends Migration
{
    public function up()
    {
        Schema::create('project_edu_rut_annexed_target_groups', function (Blueprint $table) {
            $table->id();
            $table->string('annexed_target_group_id')->unique('annexed_target_group_id_unique');
            $table->string('project_id');
            $table->string('beneficiary_name')->nullable();
            $table->text('family_background')->nullable();  // Textarea field
            $table->text('need_of_support')->nullable();    // Textarea field
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('project_id')->references('project_id')->on('projects')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_edu_rut_annexed_target_groups');
    }
}
