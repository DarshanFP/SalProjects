<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_IIES_expense_details', function (Blueprint $table) {
            $table->id();
            // Make sure this matches the relationship in your model
            $table->string('IIES_expense_id');

            $table->string('iies_particular');
            $table->decimal('iies_amount', 10, 2);

            // Optional: If you want a foreign key constraint:
            // $table->foreign('IIES_expense_id')
            //       ->references('IIES_expense_id')
            //       ->on('project_IIES_expenses')
            //       ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_IIES_expense_details');
    }
};
