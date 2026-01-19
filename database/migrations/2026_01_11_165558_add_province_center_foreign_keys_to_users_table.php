<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Ensure province column is nullable (should already be from previous migration)
            $table->string('province')->nullable()->change();

            // Add province_id foreign key
            $table->unsignedBigInteger('province_id')->nullable()->after('province');
            $table->foreign('province_id')
                ->references('id')
                ->on('provinces')
                ->onDelete('set null');
            $table->index('province_id');

            // Ensure center column is nullable
            $table->string('center')->nullable()->change();

            // Add center_id foreign key
            $table->unsignedBigInteger('center_id')->nullable()->after('center');
            $table->foreign('center_id')
                ->references('id')
                ->on('centers')
                ->onDelete('set null');
            $table->index('center_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropIndex(['province_id']);
            $table->dropColumn('province_id');

            $table->dropForeign(['center_id']);
            $table->dropIndex(['center_id']);
            $table->dropColumn('center_id');
        });
    }
};
