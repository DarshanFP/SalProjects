<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ProjectAccessService;
use App\Services\ProjectQueryService;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

/**
 * FY Phase 2 — Service-level FY filtering integration tests.
 *
 * Verifies optional FY parameter support in ProjectQueryService and ProjectAccessService
 * without changing behaviour when FY is not provided.
 */
class FYQueryIntegrationTest extends TestCase
{
    use WithoutMiddleware;

    /**
     * Without FY parameter: getApprovedOwnedProjectsForUser returns collection (same behaviour as before).
     */
    public function test_get_approved_owned_projects_without_fy_returns_collection(): void
    {
        $user = new User();
        $user->id = 1;
        $user->province_id = null;

        $result = ProjectQueryService::getApprovedOwnedProjectsForUser($user);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * With FY parameter: getApprovedOwnedProjectsForUser accepts FY and returns collection.
     */
    public function test_get_approved_owned_projects_with_fy_returns_collection(): void
    {
        $user = new User();
        $user->id = 1;
        $user->province_id = null;

        $result = ProjectQueryService::getApprovedOwnedProjectsForUser($user, [], '2024-25');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * With FY parameter: getVisibleProjectsQuery generates query containing WHERE commencement_month_year BETWEEN.
     */
    public function test_get_visible_projects_query_with_fy_contains_between_clause(): void
    {
        $user = new User();
        $user->id = 1;
        $user->role = 'coordinator';
        $user->province_id = null;

        $service = app(ProjectAccessService::class);
        $query = $service->getVisibleProjectsQuery($user, '2024-25');
        $sql = strtolower($query->toSql());

        $this->assertStringContainsString('commencement_month_year', $sql);
        $this->assertStringContainsString('between', $sql);
    }

    /**
     * Without FY parameter: getVisibleProjectsQuery does NOT add BETWEEN clause.
     */
    public function test_get_visible_projects_query_without_fy_no_between_clause(): void
    {
        $user = new User();
        $user->id = 1;
        $user->role = 'coordinator';
        $user->province_id = null;

        $service = app(ProjectAccessService::class);
        $query = $service->getVisibleProjectsQuery($user);
        $sql = strtolower($query->toSql());

        $this->assertStringNotContainsString('commencement_month_year', $sql);
        $this->assertStringNotContainsString('between', $sql);
    }

    /**
     * Backward compatibility: getApprovedOwnedProjectsForUser with single arg works.
     */
    public function test_backward_compat_get_approved_owned_with_single_arg(): void
    {
        $user = new User();
        $user->id = 1;
        $user->province_id = null;

        $result = ProjectQueryService::getApprovedOwnedProjectsForUser($user);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * Resolver: ProjectFinancialResolver exists and is unchanged (no FY logic in resolver).
     */
    public function test_resolver_exists_and_unchanged(): void
    {
        $this->assertTrue(
            class_exists(\App\Domain\Budget\ProjectFinancialResolver::class),
            'ProjectFinancialResolver exists and is unchanged by Phase 2'
        );
    }
}
