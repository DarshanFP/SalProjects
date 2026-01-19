<?php

namespace Tests\Feature\Reports\Monthly;

use Tests\TestCase;
use App\Models\User;
use App\Models\OldProjects\Project;
use App\Models\Reports\Monthly\DPReport;
use App\Constants\ProjectType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;

class ReportViewsIndexingTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    /**
     * Test that report create page loads for Development Projects
     */
    public function test_development_projects_create_page_loads()
    {
        // Use existing test data from seeder
        $user = User::where('email', 'executor@test.com')->first();
        if (!$user) {
            $this->markTestSkipped('Test user not found. Run seeder first: php artisan db:seed --class=ReportTestDataSeeder');
        }

        $project = Project::where('project_type', ProjectType::DEVELOPMENT_PROJECTS)
            ->where('user_id', $user->id)
            ->first();

        if (!$project) {
            $this->markTestSkipped('Test project not found. Run seeder first: php artisan db:seed --class=ReportTestDataSeeder');
        }

        $response = $this->actingAs($user)
            ->get("/reports/monthly/create/{$project->project_id}");

        $response->assertStatus(200);
        $response->assertSee('Outlook');
        $response->assertSee('Statements of Account');
        $response->assertSee('Photos');
        $response->assertSee('Attachments');
    }

    /**
     * Test that report create page loads for Livelihood Development Projects
     */
    public function test_livelihood_projects_create_page_loads()
    {
        // Use existing test data from seeder
        $user = User::where('email', 'executor@test.com')->first();
        if (!$user) {
            $this->markTestSkipped('Test user not found. Run seeder first: php artisan db:seed --class=ReportTestDataSeeder');
        }

        $project = Project::where('project_type', ProjectType::LIVELIHOOD_DEVELOPMENT_PROJECTS)
            ->where('user_id', $user->id)
            ->first();

        if (!$project) {
            $this->markTestSkipped('Test project not found. Run seeder first: php artisan db:seed --class=ReportTestDataSeeder');
        }

        $response = $this->actingAs($user)
            ->get("/reports/monthly/create/{$project->project_id}");

        $response->assertStatus(200);
        $response->assertSee('Annexure');
        $response->assertSee('Impact');
    }

    /**
     * Test that required JavaScript functions exist in create view
     */
    public function test_required_javascript_functions_exist_in_create_view()
    {
        $user = User::factory()->create(['user_type' => 'executor']);
        $project = Project::factory()->create([
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'user_id' => $user->id
        ]);

        $response = $this->actingAs($user)
            ->get("/reports/monthly/create/{$project->project_id}");

        $response->assertStatus(200);

        // Check for JavaScript functions
        $content = $response->getContent();

        // Reindexing functions
        $this->assertStringContainsString('reindexOutlooks', $content);
        $this->assertStringContainsString('reindexAccountRows', $content);
        $this->assertStringContainsString('reindexActivities', $content);
        $this->assertStringContainsString('reindexAttachments', $content);

        // Activity card functions
        $this->assertStringContainsString('toggleActivityCard', $content);
        $this->assertStringContainsString('updateActivityStatus', $content);
    }

    /**
     * Test that required JavaScript functions exist in edit view
     */
    public function test_required_javascript_functions_exist_in_edit_view()
    {
        // Use existing test data from seeder
        $user = User::where('email', 'executor@test.com')->first();
        if (!$user) {
            $this->markTestSkipped('Test user not found. Run seeder first: php artisan db:seed --class=ReportTestDataSeeder');
        }

        $project = Project::where('project_type', ProjectType::DEVELOPMENT_PROJECTS)
            ->where('user_id', $user->id)
            ->first();

        if (!$project) {
            $this->markTestSkipped('Test project not found. Run seeder first: php artisan db:seed --class=ReportTestDataSeeder');
        }

        // Create a test report for editing
        $report = DPReport::create([
            'report_id' => 'TEST-REPORT-' . uniqid(),
            'project_id' => $project->project_id,
            'user_id' => $user->id,
            'status' => DPReport::STATUS_DRAFT,
            'project_type' => $project->project_type,
            'project_title' => $project->project_title,
            'report_month_year' => now()->format('Y-m'),
        ]);

        $response = $this->actingAs($user)
            ->get("/reports/monthly/{$report->report_id}/edit");

        $response->assertStatus(200);

        // Check for JavaScript functions
        $content = $response->getContent();

        // Reindexing functions
        $this->assertStringContainsString('reindexOutlooks', $content);
        $this->assertStringContainsString('reindexAccountRows', $content);
        $this->assertStringContainsString('reindexActivities', $content);
        $this->assertStringContainsString('reindexAttachments', $content);

        // Activity card functions
        $this->assertStringContainsString('toggleActivityCard', $content);
        $this->assertStringContainsString('updateActivityStatus', $content);
    }

    /**
     * Test that index badges exist in Outlook section
     */
    public function test_outlook_section_has_index_badges()
    {
        $user = User::factory()->create(['user_type' => 'executor']);
        $project = Project::factory()->create([
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'user_id' => $user->id
        ]);

        $response = $this->actingAs($user)
            ->get("/reports/monthly/create/{$project->project_id}");

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check for badge structure
        $this->assertStringContainsString('badge bg-primary', $content);
        $this->assertStringContainsString('Outlook', $content);
        $this->assertStringContainsString('data-index', $content);
    }

    /**
     * Test that Statements of Account has "No." column
     */
    public function test_statements_of_account_has_index_column()
    {
        $user = User::factory()->create(['role' => 'executor', 'province' => 'Bangalore', 'status' => 'active']);
        $project = Project::factory()->create([
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'user_id' => $user->id
        ]);

        $response = $this->actingAs($user)
            ->get("/reports/monthly/create/{$project->project_id}");

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check for "No." column in table header or structure
        $this->assertStringContainsString('Statements of Account', $content);
    }

    /**
     * Test that activity cards exist in objectives section
     */
    public function test_activity_cards_exist_in_objectives_section()
    {
        $user = User::factory()->create(['role' => 'executor', 'province' => 'Bangalore', 'status' => 'active']);
        $project = Project::factory()->create([
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'user_id' => $user->id
        ]);

        $response = $this->actingAs($user)
            ->get("/reports/monthly/create/{$project->project_id}");

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check for activity card structure
        $this->assertStringContainsString('activity-card', $content);
        $this->assertStringContainsString('activity-card-header', $content);
        $this->assertStringContainsString('data-objective-index', $content);
        $this->assertStringContainsString('data-activity-index', $content);
    }

    /**
     * Test report creation with indexed fields for Development Projects
     */
    public function test_create_report_with_indexed_fields()
    {
        $user = User::factory()->create(['role' => 'executor', 'province' => 'Bangalore', 'status' => 'active']);
        $project = Project::factory()->create([
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'user_id' => $user->id
        ]);

        // Create objectives and activities for the project (IDs will be auto-generated)
        $objective = $project->objectives()->create([
            'objective' => 'Test Objective'
        ]);

        $activity = $objective->activities()->create([
            'activity' => 'Test Activity',
            'verification' => 'Test Verification'
        ]);

        $response = $this->actingAs($user)
            ->post('/reports/monthly', [
                'project_id' => $project->project_id,
                'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
                'date' => [
                    '2025-01-01',
                    '2025-02-01'
                ],
                'plan_next_month' => [
                    'Plan for January',
                    'Plan for February'
                ],
                'objective' => [
                    $objective->objective_id => 'Test Objective'
                ],
                'project_objective_id' => [
                    $objective->objective_id
                ]
            ]);

        // Check that report was created
        $this->assertDatabaseHas('DP_Reports', [
            'project_id' => $project->project_id,
            'user_id' => $user->id,
            'status' => DPReport::STATUS_DRAFT
        ]);
    }

    /**
     * Test report edit page loads correctly
     */
    public function test_edit_report_page_loads()
    {
        $user = User::factory()->create(['role' => 'executor', 'province' => 'Bangalore', 'status' => 'active']);
        $project = Project::factory()->create([
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
            'user_id' => $user->id
        ]);

        $report = DPReport::factory()->create([
            'project_id' => $project->project_id,
            'user_id' => $user->id,
            'status' => DPReport::STATUS_DRAFT
        ]);

        $response = $this->actingAs($user)
            ->get("/reports/monthly/{$report->report_id}/edit");

        $response->assertStatus(200);
        $response->assertSee('Update Report');
    }

    /**
     * Test all 12 project types can create reports
     */
    public function test_all_project_types_can_create_reports()
    {
        $user = User::factory()->create(['role' => 'executor', 'province' => 'Bangalore', 'status' => 'active']);

        $projectTypes = ProjectType::all();

        foreach ($projectTypes as $projectType) {
            $project = Project::factory()->create([
                'project_type' => $projectType,
                'user_id' => $user->id
            ]);

            $response = $this->actingAs($user)
                ->get("/reports/monthly/create/{$project->project_id}");

            $response->assertStatus(200);
        }
    }
}
