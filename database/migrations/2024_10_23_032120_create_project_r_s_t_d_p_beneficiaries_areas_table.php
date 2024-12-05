<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_RST_DP_beneficiaries_area', function (Blueprint $table) {
            $table->id();
            $table->string('DPRST_bnfcrs_area_id')->unique();
            $table->string('project_id'); // Foreign Key to project
            $table->string('project_area')->nullable();
            $table->string('category_beneficiary')->nullable();
            $table->integer('direct_beneficiaries')->nullable();
            $table->integer('indirect_beneficiaries')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_RST_DP_beneficiaries_area');
    }
};
