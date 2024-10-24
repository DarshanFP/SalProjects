<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IAH_support_details', function (Blueprint $table) {
            $table->id();
            $table->string('IAH_support_id')->unique();
            $table->string('project_id');
            $table->boolean('employed_at_st_ann')->nullable();
            $table->text('employment_details')->nullable();
            $table->boolean('received_support')->nullable();
            $table->text('support_details')->nullable();
            $table->boolean('govt_support')->nullable();
            $table->text('govt_support_nature')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IAH_support_details');
    }
};
