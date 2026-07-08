<?php

namespace Tests\Concerns;

use App\Constants\ProjectStatus;
use App\Models\OldProjects\Project;
use App\Models\Reports\Monthly\DPReport;
use App\Models\Province;
use App\Models\Society;
use App\Models\User;

/**
 * Shared fixtures for monthly report feature tests (Phase 10).
 */
trait CreatesMonthlyReportTestData
{
    /**
     * @return array{executor: User, project: Project, province: Province, society: Society, projectId: string}
     */
    protected function createReportTestContext(array $projectOverrides = []): array
    {
        $suffix = uniqid('rpt', true);
        $province = Province::query()->firstOrFail();
        $society = Society::query()->where('province_id', $province->id)->first();

        if (!$society) {
            $society = Society::create([
                'province_id' => $province->id,
                'name' => 'Test Society ' . $suffix,
                'is_active' => true,
            ]);
        }

        $executor = User::factory()->create([
            'role' => 'executor',
            'province_id' => $province->id,
            'province' => $province->name,
            'email' => 'exec-' . str_replace('.', '-', $suffix) . '@report-test.example',
        ]);

        // project_id is auto-generated on create (Project::creating hook)
        $project = Project::factory()->create(array_merge([
            'user_id' => $executor->id,
            'in_charge' => $executor->id,
            'province_id' => $province->id,
            'society_id' => $society->id,
            'society_name' => $society->name,
            'status' => ProjectStatus::APPROVED_BY_COORDINATOR,
            'current_phase' => 1,
            'goal' => 'Report test goal',
        ], $projectOverrides));

        $projectId = $project->project_id;

        return compact('executor', 'project', 'province', 'society', 'projectId');
    }

    /**
     * Create a report row with society snapshot (matches production create path).
     */
    protected function createTestReport(Project $project, User $user, string $status, string $suffix = '01'): DPReport
    {
        return DPReport::createWithProjectSnapshot([
            'report_id' => $project->project_id . '-' . $suffix,
            'project_id' => $project->project_id,
            'user_id' => $user->id,
            'project_type' => $project->project_type,
            'project_title' => $project->project_title ?? 'Test report',
            'report_month_year' => '2020-03-01',
            'status' => $status,
        ], $project);
    }
}
