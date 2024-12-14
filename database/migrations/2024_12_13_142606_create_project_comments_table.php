<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectCommentsTable extends Migration
{
    public function up()
    {
        Schema::create('project_comments', function (Blueprint $table) {
            $table->id();
            $table->string('project_comment_id')->unique();
            $table->string('project_id');
            $table->foreignId('user_id')->constrained('users');
            $table->text('comment');
            $table->timestamps();

            // Assuming 'project_id' is a primary key on 'projects' table
            // and 'projects' table has 'project_id' as the primary key:
            $table->foreign('project_id')->references('project_id')->on('projects')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_comments');
    }
}
