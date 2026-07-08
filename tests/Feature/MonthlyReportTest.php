<?php

namespace Tests\Feature;

use App\Constants\ProjectStatus;
use App\Constants\ProjectType;
use App\Models\OldProjects\Project;
use App\Models\Reports\Monthly\DPReport;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use RuntimeException;
use Tests\Concerns\CreatesMonthlyReportTestData;
use Tests\TestCase;

/**
 * Phase 10 — Monthly report workflow regression tests (Phases 1, 2, 5).
 */
class MonthlyReportTest extends TestCase
{
    use CreatesMonthlyReportTestData;
    use DatabaseTransactions;

    public function test_executor_can_create_draft_report_for_approved_project(): void
    {
        ['executor' => $executor, 'project' => $project, 'projectId' => $projectId] = $this->createReportTestContext();

        $response = $this->actingAs($executor)->post(route('monthly.report.store'), [
            'project_id' => $projectId,
            'save_as_draft' => '1',
            'project_title' => $project->project_title,
            'project_type' => $project->project_type,
            'report_month' => 3,
            'report_year' => 2020,
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('edit', $response->headers->get('Location') ?? '');

        $report = DPReport::where('project_id', $projectId)->first();
        $this->assertNotNull($report);
        $this->assertSame(DPReport::STATUS_DRAFT, $report->status);
        $this->assertSame($project->society_id, $report->society_id);
    }

    public function test_create_fails_without_society_id_on_project(): void
    {
        $project = new Project();
        $project->project_id = 'TEST-NO-SOC-' . uniqid();
        $project->society_id = null;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('has no society_id');

        DPReport::createWithProjectSnapshot([
            'report_id' => $project->project_id . '-01',
            'user_id' => 1,
            'project_id' => $project->project_id,
            'status' => DPReport::STATUS_DRAFT,
            'project_type' => ProjectType::DEVELOPMENT_PROJECTS,
        ], $project);
    }

    public function test_create_rejected_for_unapproved_project(): void
    {
        ['executor' => $executor, 'projectId' => $projectId] = $this->createReportTestContext([
            'status' => ProjectStatus::DRAFT,
        ]);

        $response = $this->actingAs($executor)->post(route('monthly.report.store'), [
            'project_id' => $projectId,
            'save_as_draft' => '1',
            'report_month' => 4,
            'report_year' => 2020,
        ]);

        $response->assertForbidden();
        $this->assertNull(DPReport::where('project_id', $projectId)->first());
    }

    public function test_executor_can_edit_reverted_to_executor_report(): void
    {
        ['executor' => $executor, 'project' => $project, 'projectId' => $projectId] = $this->createReportTestContext();

        $report = $this->createTestReport($project, $executor, DPReport::STATUS_REVERTED_TO_EXECUTOR, '01');

        $response = $this->actingAs($executor)->get(route('monthly.report.edit', $report->report_id));

        $response->assertOk();
    }

    public function test_executor_cannot_edit_submitted_report(): void
    {
        ['executor' => $executor, 'project' => $project, 'projectId' => $projectId] = $this->createReportTestContext();

        $report = $this->createTestReport($project, $executor, DPReport::STATUS_SUBMITTED_TO_PROVINCIAL, '02');

        $response = $this->actingAs($executor)->get(route('monthly.report.edit', $report->report_id));

        $response->assertForbidden();
    }

    public function test_unauthenticated_cannot_access_quarterly_routes(): void
    {
        $response = $this->get(route('quarterly.developmentProject.index'));

        $response->assertRedirect(route('login'));
    }
}
