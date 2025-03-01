<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectIESExpenseDetailsTable extends Migration
{
    public function up()
    {
        Schema::create('project_IES_expense_details', function (Blueprint $table) {
            $table->id();
            $table->string('IES_expense_id'); // Match the type from the parent table
            $table->string('particular');
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            // Foreign Key Constraint
            $table->foreign('IES_expense_id')
                  ->references('IES_expense_id') // Referencing the unique column in parent table
                  ->on('project_IES_expenses')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_IES_expense_details');
    }
}
