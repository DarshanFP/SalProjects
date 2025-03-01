<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
{
    Schema::create('project_IIES_immediate_family_details', function (Blueprint $table) {
        $table->id();
        $table->string('IIES_family_detail_id')->unique('unique_iies_famdet_id'); // Shortened constraint name
        $table->string('project_id'); // Foreign key reference
        $table->boolean('iies_mother_expired')->default(0);
        $table->boolean('iies_father_expired')->default(0);
        $table->boolean('iies_grandmother_support')->default(0);
        $table->boolean('iies_grandfather_support')->default(0);
        $table->boolean('iies_father_deserted')->default(0);
        $table->string('iies_family_details_others')->nullable();
        $table->boolean('iies_father_sick')->default(0);
        $table->boolean('iies_father_hiv_aids')->default(0);
        $table->boolean('iies_father_disabled')->default(0);
        $table->boolean('iies_father_alcoholic')->default(0);
        $table->string('iies_father_health_others')->nullable();
        $table->boolean('iies_mother_sick')->default(0);
        $table->boolean('iies_mother_hiv_aids')->default(0);
        $table->boolean('iies_mother_disabled')->default(0);
        $table->boolean('iies_mother_alcoholic')->default(0);
        $table->string('iies_mother_health_others')->nullable();
        $table->boolean('iies_own_house')->default(0);
        $table->boolean('iies_rented_house')->default(0);
        $table->string('iies_residential_others')->nullable();
        $table->text('iies_family_situation')->nullable();
        $table->text('iies_assistance_need')->nullable();
        $table->boolean('iies_received_support')->default(0);
        $table->text('iies_support_details')->nullable();
        $table->boolean('iies_employed_with_stanns')->default(0);
        $table->text('iies_employment_details')->nullable();
        $table->timestamps();
    });
}


    public function down(): void
    {
        Schema::dropIfExists('project_IIES_immediate_family_details');
    }
};
