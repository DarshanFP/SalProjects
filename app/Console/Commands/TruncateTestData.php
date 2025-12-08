<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TruncateTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:truncate-test-data 
                            {--force : Force the operation to run without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate all test data tables while keeping Core System Tables (users, permissions, roles) intact';

    /**
     * Tables to truncate (excluding Core System Tables)
     *
     * @var array
     */
    protected $tablesToTruncate = [
        // Report Management Tables
        'DP_Reports',
        'DP_Objectives',
        'DP_Activities',
        'DP_AccountDetails',
        'DP_Photos',
        'DP_Outlooks',
        'qrdl_annexure',
        'rqis_age_profiles',
        'rqst_trainee_profile',
        'rqwd_inmates_profiles',
        'report_attachments',
        'report_comments',
        
        // Project Management Tables
        'projects',
        'project_budgets',
        'project_attachments',
        'project_objectives',
        'project_results',
        'project_risks',
        'project_activities',
        'project_timeframes',
        'project_sustainabilities',
        'project_comments',
        
        // Project Type-Specific Tables - EduRUT
        'Project_EduRUT_Basic_Info',
        'project_edu_rut_target_groups',
        'project_edu_rut_annexed_target_groups',
        
        // Project Type-Specific Tables - CIC
        'project_cic_basic_info',
        
        // Project Type-Specific Tables - CCI
        'project_CCI_rationale',
        'project_CCI_statistics',
        'project_CCI_age_profile',
        'project_CCI_annexed_target_group',
        'project_CCI_personal_situation',
        'project_CCI_economic_background',
        'project_CCI_present_situation',
        'project_CCI_achievements',
        
        // Project Type-Specific Tables - LDP
        'project_LDP_need_analysis',
        'project_LDP_target_group',
        'project_LDP_intervention_logic',
        
        // Project Type-Specific Tables - RST
        'project_RST_institution_info',
        'project_RST_target_group',
        'project_RST_target_group_annexure',
        'project_RST_geographical_areas',
        'project_RST_DP_beneficiaries_area',
        
        // Project Type-Specific Tables - IGE
        'project_IGE_institution_info',
        'project_IGE_beneficiaries_supported',
        'project_IGE_ongoing_beneficiaries',
        'project_IGE_new_beneficiaries',
        'project_IGE_budget',
        'project_IGE_development_monitoring',
        
        // Project Type-Specific Tables - IES
        'project_IES_personal_info',
        'project_IES_immediate_family_details',
        'project_IES_family_working_members',
        'project_IES_education_background',
        'project_IES_expenses',
        'project_IES_expense_details',
        'project_IES_attachments',
        
        // Project Type-Specific Tables - IIES
        'project_IIES_personal_info',
        'project_IIES_immediate_family_details',
        'project_IIES_family_working_members',
        'project_IIES_education_background',
        'project_IIES_scope_financial_support',
        'project_IIES_expenses',
        'project_IIES_expense_details',
        'project_IIES_attachments',
        
        // Project Type-Specific Tables - ILP
        'project_ILP_personal_info',
        'project_ILP_budget',
        'project_ILP_strength_weakness',
        'project_ILP_revenue_goals',
        'project_ILP_risk_analysis',
        'project_ILP_attached_docs',
        
        // Project Type-Specific Tables - IAH
        'project_IAH_personal_info',
        'project_IAH_health_condition',
        'project_IAH_earning_members',
        'project_IAH_budget_details',
        'project_IAH_support_details',
        'project_IAH_documents',
        
        // Legacy/Old Development Projects
        'oldDevelopmentProjects',
        'old_DP_budgets',
        'old_DP_attachments',
    ];

    /**
     * Core System Tables that will NOT be truncated
     *
     * @var array
     */
    protected $coreSystemTables = [
        'users',
        'password_reset_tokens',
        'sessions',
        'failed_jobs',
        'personal_access_tokens',
        'permissions',
        'roles',
        'model_has_permissions',
        'model_has_roles',
        'role_has_permissions',
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║     Truncate Test Data - Production Preparation              ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Display warning
        $this->warn('⚠️  WARNING: This will DELETE ALL DATA from the following tables:');
        $this->newLine();
        $this->line('   • ' . count($this->tablesToTruncate) . ' tables will be truncated');
        $this->line('   • Core System Tables will be preserved:');
        foreach ($this->coreSystemTables as $table) {
            $this->line('     - ' . $table);
        }
        $this->newLine();

        // Confirmation
        if (!$this->option('force')) {
            if (!$this->confirm('Do you want to continue? This action cannot be undone!', false)) {
                $this->info('Operation cancelled.');
                return Command::FAILURE;
            }
        }

        // Check if tables exist
        $this->info('Checking tables...');
        $existingTables = [];
        $missingTables = [];

        foreach ($this->tablesToTruncate as $table) {
            if (Schema::hasTable($table)) {
                $existingTables[] = $table;
            } else {
                $missingTables[] = $table;
            }
        }

        if (!empty($missingTables)) {
            $this->warn('The following tables do not exist and will be skipped:');
            foreach ($missingTables as $table) {
                $this->line('  - ' . $table);
            }
            $this->newLine();
        }

        if (empty($existingTables)) {
            $this->error('No tables found to truncate!');
            return Command::FAILURE;
        }

        $this->info('Found ' . count($existingTables) . ' tables to truncate.');
        $this->newLine();

        // Show progress bar
        $bar = $this->output->createProgressBar(count($existingTables));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $bar->setMessage('Starting truncation...');
        $bar->start();

        try {
            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

            $truncatedCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($existingTables as $table) {
                $bar->setMessage("Truncating {$table}...");
                
                try {
                    DB::table($table)->truncate();
                    $truncatedCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = [
                        'table' => $table,
                        'error' => $e->getMessage()
                    ];
                }
                
                $bar->advance();
            }

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

            $bar->setMessage('Completed!');
            $bar->finish();
            $this->newLine(2);

            // Display results
            if ($errorCount === 0) {
                $this->info("✅ Successfully truncated {$truncatedCount} tables!");
            } else {
                $this->warn("⚠️  Truncated {$truncatedCount} tables with {$errorCount} errors:");
                foreach ($errors as $error) {
                    $this->error("  • {$error['table']}: {$error['error']}");
                }
            }

            $this->newLine();
            $this->info('Core System Tables preserved:');
            foreach ($this->coreSystemTables as $table) {
                if (Schema::hasTable($table)) {
                    $count = DB::table($table)->count();
                    $this->line("  ✓ {$table}: {$count} records");
                }
            }

            $this->newLine();
            $this->info('✨ Operation completed successfully!');
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            // Re-enable foreign key checks in case of error
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
            
            $this->newLine(2);
            $this->error('❌ An error occurred during truncation:');
            $this->error($e->getMessage());
            
            return Command::FAILURE;
        }
    }
}

