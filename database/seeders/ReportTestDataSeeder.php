<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\ProjectObjective;
use App\Models\OldProjects\ProjectActivity;
use App\Models\OldProjects\ProjectResult;
use App\Models\OldProjects\ProjectTimeframe;
use App\Constants\ProjectType;

class ReportTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates test projects for all 12 project types with objectives and activities
     * for testing report views enhancement.
     */
    public function run(): void
    {
        // Create test users
        $executor = User::firstOrCreate(
            ['email' => 'executor@test.com'],
            [
                'name' => 'Test Executor',
                'password' => bcrypt('password'),
                'role' => 'executor',
                'province' => 'Bangalore',
                'status' => 'active'
            ]
        );

        $provincial = User::firstOrCreate(
            ['email' => 'provincial@test.com'],
            [
                'name' => 'Test Provincial',
                'password' => bcrypt('password'),
                'role' => 'provincial',
                'province' => 'Bangalore',
                'status' => 'active'
            ]
        );

        $coordinator = User::firstOrCreate(
            ['email' => 'coordinator@test.com'],
            [
                'name' => 'Test Coordinator',
                'password' => bcrypt('password'),
                'role' => 'coordinator',
                'province' => 'Generalate',
                'status' => 'active'
            ]
        );

        // All project types to create
        $projectTypes = ProjectType::all();

        foreach ($projectTypes as $index => $projectType) {
            // Check if project already exists with this type for this user
            $existingProject = Project::where('project_type', $projectType)
                ->where('user_id', $executor->id)
                ->first();

            if ($existingProject) {
                $project = $existingProject;
            } else {
                $project = Project::create([
                    'project_type' => $projectType,
                    'project_title' => 'Test Project: ' . $projectType,
                    'user_id' => $executor->id,
                    'in_charge' => $executor->id,
                    'status' => 'approved_by_coordinator',
                    'overall_project_budget' => '100000',
                    'society_name' => 'Test Society',
                    'executor_name' => 'Test Executor Name'
                ]);
            }

            // Create objectives and activities for each project (if not already exist)
            $existingObjectives = $project->objectives()->count();
            if ($existingObjectives == 0) {
                for ($objIndex = 1; $objIndex <= 3; $objIndex++) {
                    $objective = ProjectObjective::create([
                        'project_id' => $project->project_id,
                        'objective' => "Test Objective {$objIndex} for {$projectType}"
                    ]);

                    // Create results for objective
                    for ($resIndex = 1; $resIndex <= 2; $resIndex++) {
                        ProjectResult::create([
                            'objective_id' => $objective->objective_id,
                            'result' => "Test Result {$resIndex} for Objective {$objIndex}"
                        ]);
                    }

                    // Create activities for objective
                    for ($actIndex = 1; $actIndex <= 3; $actIndex++) {
                        $activity = ProjectActivity::create([
                            'objective_id' => $objective->objective_id,
                            'activity' => "Test Activity {$actIndex} for Objective {$objIndex}",
                            'verification' => "Test verification for Activity {$actIndex}"
                        ]);

                        // Create timeframes for activity (schedule across multiple months)
                        $months = [1, 2, 3, 4, 5, 6]; // First 6 months
                        foreach ($months as $monthIndex => $month) {
                            ProjectTimeframe::create([
                                'activity_id' => $activity->activity_id,
                                'month' => $month,
                                'is_active' => ($monthIndex < 3) // First 3 months active
                            ]);
                        }
                    }
                }
            }

            $this->command->info("Created test project: {$project->project_id} ({$projectType})");
        }

        $this->command->info("✅ Test data seeding complete!");
        $this->command->info("Created test projects for all " . count($projectTypes) . " project types");
        $this->command->info("Test users:");
        $this->command->info("  - Executor: executor@test.com / password");
        $this->command->info("  - Provincial: provincial@test.com / password");
        $this->command->info("  - Coordinator: coordinator@test.com / password");
    }
}
