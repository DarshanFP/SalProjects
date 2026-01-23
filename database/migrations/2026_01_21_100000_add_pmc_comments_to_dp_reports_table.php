<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * PMC: Comments by Project Monitoring Committee — filled by provincial before forwarding to coordinator.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('DP_Reports', function (Blueprint $table) {
            $table->text('pmc_comments')->nullable()->after('revert_reason');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('DP_Reports', function (Blueprint $table) {
            $table->dropColumn('pmc_comments');
        });
    }
};
