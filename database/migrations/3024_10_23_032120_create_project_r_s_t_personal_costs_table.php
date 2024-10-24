<?php

// use Illuminate\Database\Migrations\Migration;
// use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Support\Facades\Schema;

// return new class extends Migration {
//     public function up(): void
//     {
//         Schema::create('project_RST_personal_cost', function (Blueprint $table) {
//             $table->id();
//             $table->string('personal_cost_id')->unique();
//             $table->string('project_id'); // Foreign Key to project
//             $table->string('particular');
//             $table->integer('nr_staff');
//             $table->decimal('rate', 10, 2);
//             $table->decimal('year_1', 10, 2)->nullable();
//             $table->decimal('year_2', 10, 2)->nullable();
//             $table->decimal('year_3', 10, 2)->nullable();
//             $table->decimal('year_4', 10, 2)->nullable();
//             $table->timestamps();
//         });
//     }

//     public function down(): void
//     {
//         Schema::dropIfExists('project_RST_personal_cost');
//     }
// };
