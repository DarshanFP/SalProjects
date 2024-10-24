<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IIES_education_background', function (Blueprint $table) {
            $table->id();
            $table->string('IIES_education_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->string('prev_education'); // Previous academic qualification
            $table->string('prev_institution'); // Previous institution name
            $table->string('prev_insti_address'); // Previous institution address
            $table->decimal('prev_marks', 5, 2); // Marks percentage
            $table->string('current_studies'); // Current studies
            $table->string('curr_institution'); // Present institution name
            $table->string('curr_insti_address'); // Present institution address
            $table->text('aspiration')->nullable(); // Educational aspirations
            $table->text('long_term_effect')->nullable(); // Long-term effect of the support
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IIES_education_background');
    }
};
