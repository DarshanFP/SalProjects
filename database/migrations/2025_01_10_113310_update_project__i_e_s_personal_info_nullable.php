<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateProjectIESPersonalInfoNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_IES_personal_info', function (Blueprint $table) {
            // Modify columns to allow NULL values
            $table->string('bname', 255)->nullable()->change();
            $table->integer('age')->nullable()->change();
            $table->string('gender', 50)->nullable()->change();
            $table->date('dob')->nullable()->change();
            $table->string('email', 255)->nullable()->change();
            $table->string('contact', 255)->nullable()->change();
            $table->string('aadhar', 255)->nullable()->change();
            $table->text('full_address')->nullable()->change();
            $table->string('father_name', 255)->nullable()->change();
            $table->string('mother_name', 255)->nullable()->change();
            $table->string('mother_tongue', 255)->nullable()->change();
            $table->string('current_studies', 255)->nullable()->change();
            $table->string('bcaste', 255)->nullable()->change();
            $table->string('father_occupation', 255)->nullable()->change();
            $table->decimal('father_income', 10, 2)->nullable()->change();
            $table->string('mother_occupation', 255)->nullable()->change();
            $table->decimal('mother_income', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('project_IES_personal_info', function (Blueprint $table) {
            // Revert columns to NOT NULL if necessary (default values are optional)
            $table->string('bname', 255)->nullable(false)->change();
            $table->integer('age')->nullable(false)->change();
            $table->string('gender', 50)->nullable(false)->change();
            $table->date('dob')->nullable(false)->change();
            $table->string('email', 255)->nullable(false)->change();
            $table->string('contact', 255)->nullable(false)->change();
            $table->string('aadhar', 255)->nullable(false)->change();
            $table->text('full_address')->nullable(false)->change();
            $table->string('father_name', 255)->nullable(false)->change();
            $table->string('mother_name', 255)->nullable(false)->change();
            $table->string('mother_tongue', 255)->nullable(false)->change();
            $table->string('current_studies', 255)->nullable(false)->change();
            $table->string('bcaste', 255)->nullable(false)->change();
            $table->string('father_occupation', 255)->nullable(false)->change();
            $table->decimal('father_income', 10, 2)->nullable(false)->change();
            $table->string('mother_occupation', 255)->nullable(false)->change();
            $table->decimal('mother_income', 10, 2)->nullable(false)->change();
        });
    }
}
