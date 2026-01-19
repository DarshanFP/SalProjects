<?php

/**
 * Reports Data Truncation Script
 *
 * This script will:
 * 1. Truncate all report-related tables
 * 2. Clean up uploaded report attachments
 * 3. Preserve the users table
 *
 * WARNING: This will permanently delete all report data!
 * Run this script only when you want to start fresh.
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

echo "=== Reports Data Truncation Script ===\n";
echo "WARNING: This will permanently delete all report data!\n";
echo "Only the users table will be preserved.\n\n";

// Ask for confirmation
echo "Are you sure you want to proceed? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) !== 'yes') {
    echo "Operation cancelled.\n";
    exit(0);
}

echo "\nStarting reports data truncation...\n\n";

try {
    // Disable foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS = 0');

    // List of report-related tables to truncate
    $reportTables = [
        // Main report tables
        'DP_Reports',
        'report_attachments',
        'report_comments',

        // DP (Development Project) related tables
        'DP_Activities',
        'DP_Objectives',
        'DP_AccountDetails',
        'DP_Outlooks',
        'DP_Photos',

        // RQ (Request) related tables
        'rqst_trainee_profile',
        'rqis_age_profiles',
        'rqwd_inmates_profiles',

        // QRDL related tables
        'qrdl_annexure',
    ];

    echo "Truncating report tables:\n";
    foreach ($reportTables as $table) {
        try {
            $count = DB::table($table)->count();
            DB::table($table)->truncate();
            echo "✓ Truncated table '{$table}' ({$count} records)\n";
        } catch (Exception $e) {
            echo "✗ Error truncating table '{$table}': " . $e->getMessage() . "\n";
        }
    }

    // Reset auto-increment counters
    echo "\nResetting auto-increment counters:\n";
    foreach ($reportTables as $table) {
        try {
            DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
            echo "✓ Reset auto-increment for '{$table}'\n";
        } catch (Exception $e) {
            echo "✗ Error resetting auto-increment for '{$table}': " . $e->getMessage() . "\n";
        }
    }

    // Re-enable foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS = 1');

    echo "\nCleaning up report attachments:\n";

    // Clean up report attachments from storage
    $reportAttachmentPaths = [
        'public/report_attachments',
        'public/ReportImages',
        'public/Reports',
    ];

    foreach ($reportAttachmentPaths as $path) {
        try {
            if (Storage::exists($path)) {
                $files = Storage::allFiles($path);
                $count = count($files);

                foreach ($files as $file) {
                    Storage::delete($file);
                }

                echo "✓ Deleted {$count} files from '{$path}'\n";
            } else {
                echo "✓ Directory '{$path}' does not exist (already clean)\n";
            }
        } catch (Exception $e) {
            echo "✗ Error cleaning up '{$path}': " . $e->getMessage() . "\n";
        }
    }

    // Clean up any remaining report-related files in other directories
    $otherPaths = [
        'public/attachments',
        'public/photos',
    ];

    foreach ($otherPaths as $path) {
        try {
            if (Storage::exists($path)) {
                $files = Storage::allFiles($path);
                $deletedCount = 0;

                foreach ($files as $file) {
                    // Check if file is report-related (you can customize this logic)
                    if (strpos($file, 'report') !== false ||
                        strpos($file, 'Report') !== false ||
                        strpos($file, 'DP_') !== false) {
                        Storage::delete($file);
                        $deletedCount++;
                    }
                }

                if ($deletedCount > 0) {
                    echo "✓ Deleted {$deletedCount} report-related files from '{$path}'\n";
                } else {
                    echo "✓ No report-related files found in '{$path}'\n";
                }
            }
        } catch (Exception $e) {
            echo "✗ Error cleaning up '{$path}': " . $e->getMessage() . "\n";
        }
    }

    echo "\n=== Reports Data Truncation Completed Successfully! ===\n";
    echo "✓ All report tables have been truncated\n";
    echo "✓ Auto-increment counters have been reset\n";
    echo "✓ Report attachments have been cleaned up\n";
    echo "✓ Users table has been preserved\n\n";

    echo "You can now start fresh with clean report data.\n";

} catch (Exception $e) {
    echo "\n✗ Error during truncation: " . $e->getMessage() . "\n";
    echo "Please check the error and try again.\n";

    // Re-enable foreign key checks in case of error
    DB::statement('SET FOREIGN_KEY_CHECKS = 1');
}
