<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_id')->unique();
            $table->unsignedBigInteger('user_id');
            $table->string('project_type');
            $table->string('project_title')->nullable();
            $table->string('society_name')->nullable();
            $table->string('president_name')->nullable();
            $table->unsignedBigInteger('in_charge');
            $table->string('in_charge_name')->nullable();
            $table->string('in_charge_mobile')->nullable();
            $table->string('in_charge_email')->nullable();
            $table->string('executor_name')->nullable();
            $table->string('executor_mobile')->nullable();
            $table->string('executor_email')->nullable();
            $table->text('full_address')->nullable();
            $table->integer('overall_project_period')->nullable();
            $table->integer('current_phase')->nullable();
            $table->date('commencement_month_year')->nullable();
            $table->decimal('overall_project_budget', 10, 2)->default(0.00);
            $table->decimal('amount_forwarded', 10, 2)->nullable();
            $table->decimal('amount_sanctioned', 10, 2)->nullable();
            $table->decimal('opening_balance', 10, 2)->nullable();
            $table->string('coordinator_india_name')->nullable();
            $table->string('coordinator_india_phone')->nullable();
            $table->string('coordinator_india_email')->nullable();
            $table->string('coordinator_luzern_name')->nullable();
            $table->string('coordinator_luzern_phone')->nullable();
            $table->string('coordinator_luzern_email')->nullable();
            $table->string('status')->default(value: 'underwriting'); // Added the status column
            $table->text('goal');

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('in_charge')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
}
