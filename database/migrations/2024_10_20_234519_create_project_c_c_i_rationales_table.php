<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_CCI_rationale', function (Blueprint $table) {
            $table->id();
            $table->string('CCI_rationale_id')->unique();
            $table->string('project_id');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_CCI_rationale');
    }
};
