<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TruncateReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:truncate {--force : Force truncation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate all report-related tables and clean up attachments while preserving users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Reports Data Truncation Command ===');
        $this->warn('WARNING: This will permanently delete all report data!');
        $this->warn('Only the users table will be preserved.');

        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to proceed?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Starting reports data truncation...');

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

            $this->info('Truncating report tables:');
            $progressBar = $this->output->createProgressBar(count($reportTables));
            $progressBar->start();

            foreach ($reportTables as $table) {
                try {
                    $count = DB::table($table)->count();
                    DB::table($table)->truncate();
                    $this->line("\n✓ Truncated table '{$table}' ({$count} records)");
                } catch (\Exception $e) {
                    $this->error("\n✗ Error truncating table '{$table}': " . $e->getMessage());
                }
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

            // Reset auto-increment counters
            $this->info('Resetting auto-increment counters:');
            foreach ($reportTables as $table) {
                try {
                    DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
                    $this->line("✓ Reset auto-increment for '{$table}'");
                } catch (\Exception $e) {
                    $this->error("✗ Error resetting auto-increment for '{$table}': " . $e->getMessage());
                }
            }

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');

            $this->info('Cleaning up report attachments:');

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

                        $this->line("✓ Deleted {$count} files from '{$path}'");
                    } else {
                        $this->line("✓ Directory '{$path}' does not exist (already clean)");
                    }
                } catch (\Exception $e) {
                    $this->error("✗ Error cleaning up '{$path}': " . $e->getMessage());
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
                            // Check if file is report-related
                            if (strpos($file, 'report') !== false ||
                                strpos($file, 'Report') !== false ||
                                strpos($file, 'DP_') !== false) {
                                Storage::delete($file);
                                $deletedCount++;
                            }
                        }

                        if ($deletedCount > 0) {
                            $this->line("✓ Deleted {$deletedCount} report-related files from '{$path}'");
                        } else {
                            $this->line("✓ No report-related files found in '{$path}'");
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("✗ Error cleaning up '{$path}': " . $e->getMessage());
                }
            }

            $this->newLine();
            $this->info('=== Reports Data Truncation Completed Successfully! ===');
            $this->info('✓ All report tables have been truncated');
            $this->info('✓ Auto-increment counters have been reset');
            $this->info('✓ Report attachments have been cleaned up');
            $this->info('✓ Users table has been preserved');
            $this->newLine();
            $this->info('You can now start fresh with clean report data.');

            return 0;

        } catch (\Exception $e) {
            $this->error('Error during truncation: ' . $e->getMessage());
            $this->error('Please check the error and try again.');

            // Re-enable foreign key checks in case of error
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');

            return 1;
        }
    }
}
