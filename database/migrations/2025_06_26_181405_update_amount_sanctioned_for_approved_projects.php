<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateAmountSanctionedForApprovedProjects extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update all approved projects to set amount_sanctioned from overall_project_budget
        // where amount_sanctioned is null or 0 and overall_project_budget is greater than 0
        DB::table('projects')
            ->where('status', 'approved_by_coordinator')
            ->where(function($query) {
                $query->whereNull('amount_sanctioned')
                      ->orWhere('amount_sanctioned', 0);
            })
            ->where('overall_project_budget', '>', 0)
            ->update([
                'amount_sanctioned' => DB::raw('overall_project_budget'),
                'updated_at' => now()
            ]);

        // Log the number of updated records
        $updatedCount = DB::table('projects')
            ->where('status', 'approved_by_coordinator')
            ->where('amount_sanctioned', '>', 0)
            ->count();

        \Log::info('Migration: Updated amount_sanctioned for approved projects', [
            'updated_count' => $updatedCount,
            'migration' => 'update_amount_sanctioned_for_approved_projects'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as it updates data
        // We could set amount_sanctioned back to null/0, but that would lose data
        // So we'll leave this empty
    }
}
