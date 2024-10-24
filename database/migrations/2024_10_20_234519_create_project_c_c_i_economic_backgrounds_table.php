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
        Schema::create('project_CCI_economic_background', function (Blueprint $table) {
            $table->id();
            $table->string('CCI_eco_bg_id')->unique();
            $table->string('project_id'); // Foreign key to project

            // Economic background of parents
            $table->integer('agricultural_labour_number')->nullable();
            $table->integer('marginal_farmers_number')->nullable();
            $table->integer('self_employed_parents_number')->nullable();
            $table->integer('informal_sector_parents_number')->nullable();
            $table->integer('any_other_number')->nullable();

            // General remarks
            $table->text('general_remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_CCI_economic_background');
    }
};
