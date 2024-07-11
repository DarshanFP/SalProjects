<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create the 'permissions' table
        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name'); // For MySQL 8.0 use string('name', 125);
            $table->string('guard_name'); // For MySQL 8.0 use string('guard_name', 125);
            $table->timestamps();
        });

        // Create the 'roles' table
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name'); // For MySQL 8.0 use string('name', 125);
            $table->string('guard_name'); // For MySQL 8.0 use string('guard_name', 125);
            $table->timestamps();
        });

        // Create the 'model_has_permissions' table
        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');

            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');

            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        // Create the 'model_has_roles' table
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');

            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');

            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        // Create the 'role_has_permissions' table
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');

            $table->primary(['permission_id', 'role_id']);
        });

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
}
