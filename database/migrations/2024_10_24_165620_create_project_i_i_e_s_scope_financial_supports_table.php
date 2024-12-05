<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_IIES_scope_financial_support', function (Blueprint $table) {
            $table->id();
            $table->string('IIES_fin_sup_id')->unique();
            $table->string('project_id'); // Foreign key to Project
            $table->boolean('govt_eligible_scholarship')->default(false); // Government scholarship eligibility
            $table->decimal('scholarship_amt', 10, 2)->nullable(); // Expected amount of scholarship
            $table->boolean('other_eligible_scholarship')->default(false); // Eligibility for other scholarships
            $table->decimal('other_scholarship_amt', 10, 2)->nullable(); // Expected amount of other scholarships
            $table->decimal('family_contrib', 10, 2)->nullable(); // Family's contribution
            $table->text('no_contrib_reason')->nullable(); // Reason for no family contribution
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IIES_scope_financial_support');
    }
};
