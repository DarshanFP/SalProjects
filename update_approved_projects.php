<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "Starting to update approved projects...\n";

try {
    // Get count of approved projects that need updating
    $projectsToUpdate = DB::table('projects')
        ->where('status', 'approved_by_coordinator')
        ->where(function($query) {
            $query->whereNull('amount_sanctioned')
                  ->orWhere('amount_sanctioned', 0);
        })
        ->where('overall_project_budget', '>', 0)
        ->count();

    echo "Found {$projectsToUpdate} approved projects to update.\n";

    if ($projectsToUpdate > 0) {
        // Update the projects
        $updatedCount = DB::table('projects')
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

        echo "Successfully updated {$updatedCount} projects.\n";

        // Verify the update
        $verifiedCount = DB::table('projects')
            ->where('status', 'approved_by_coordinator')
            ->where('amount_sanctioned', '>', 0)
            ->count();

        echo "Verified: {$verifiedCount} approved projects now have amount_sanctioned > 0.\n";

        // Log the action
        Log::info('Updated amount_sanctioned for approved projects', [
            'updated_count' => $updatedCount,
            'verified_count' => $verifiedCount
        ]);
    } else {
        echo "No projects need updating.\n";
    }

    // Show summary
    echo "\nSummary:\n";
    echo "Total approved projects: " . DB::table('projects')->where('status', 'approved_by_coordinator')->count() . "\n";
    echo "Projects with amount_sanctioned > 0: " . DB::table('projects')->where('amount_sanctioned', '>', 0)->count() . "\n";
    echo "Projects with overall_project_budget > 0: " . DB::table('projects')->where('overall_project_budget', '>', 0)->count() . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    Log::error('Error updating approved projects', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

echo "Script completed.\n";
