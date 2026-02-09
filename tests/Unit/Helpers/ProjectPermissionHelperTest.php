<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Helpers\ProjectPermissionHelper;
use App\Models\OldProjects\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ProjectPermissionHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_allows_owner_to_edit_project()
    {
        $user = User::factory()->create(['role' => 'executor']);
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        $result = ProjectPermissionHelper::canEdit($project, $user);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_prevents_non_owner_from_editing_project()
    {
        $owner = User::factory()->create(['role' => 'executor']);
        $otherUser = User::factory()->create(['role' => 'executor']);
        $project = Project::factory()->create([
            'user_id' => $owner->id,
            'status' => 'draft',
        ]);

        $result = ProjectPermissionHelper::canEdit($project, $otherUser);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_allows_in_charge_to_edit_project()
    {
        $owner = User::factory()->create(['role' => 'executor']);
        $inCharge = User::factory()->create(['role' => 'executor']);
        $project = Project::factory()->create([
            'user_id' => $owner->id,
            'in_charge_user_id' => $inCharge->id,
            'status' => 'draft',
        ]);

        $result = ProjectPermissionHelper::canEdit($project, $inCharge);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_allows_coordinator_to_edit_approved_projects()
    {
        $coordinator = User::factory()->create(['role' => 'coordinator']);
        $project = Project::factory()->create([
            'status' => 'approved',
        ]);

        $result = ProjectPermissionHelper::canEdit($project, $coordinator);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_prevents_editing_submitted_projects()
    {
        $user = User::factory()->create(['role' => 'executor']);
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'status' => 'submitted_to_provincial',
        ]);

        $result = ProjectPermissionHelper::canEdit($project, $user);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_checks_if_user_is_owner()
    {
        $user = User::factory()->create(['role' => 'executor']);
        $project = Project::factory()->create([
            'user_id' => $user->id,
        ]);

        $result = ProjectPermissionHelper::isOwner($project, $user);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_checks_if_user_is_not_owner()
    {
        $owner = User::factory()->create(['role' => 'executor']);
        $otherUser = User::factory()->create(['role' => 'executor']);
        $project = Project::factory()->create([
            'user_id' => $owner->id,
        ]);

        $result = ProjectPermissionHelper::isOwner($project, $otherUser);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_checks_if_user_is_owner_or_in_charge()
    {
        $owner = User::factory()->create(['role' => 'executor']);
        $inCharge = User::factory()->create(['role' => 'executor']);
        $project = Project::factory()->create([
            'user_id' => $owner->id,
            'in_charge_user_id' => $inCharge->id,
        ]);

        $this->assertTrue(ProjectPermissionHelper::isOwnerOrInCharge($project, $owner));
        $this->assertTrue(ProjectPermissionHelper::isOwnerOrInCharge($project, $inCharge));
    }

    /** @test */
    public function it_checks_if_user_can_submit_project()
    {
        $user = User::factory()->create(['role' => 'executor']);
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        $result = ProjectPermissionHelper::canSubmit($project, $user);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_prevents_submitting_already_submitted_project()
    {
        $user = User::factory()->create(['role' => 'executor']);
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'status' => 'submitted_to_provincial',
        ]);

        $result = ProjectPermissionHelper::canSubmit($project, $user);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_checks_if_user_can_view_project()
    {
        $user = User::factory()->create(['role' => 'executor']);
        $project = Project::factory()->create([
            'user_id' => $user->id,
        ]);

        $result = ProjectPermissionHelper::canView($project, $user);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_allows_admin_to_view_any_project()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create();

        $result = ProjectPermissionHelper::canView($project, $admin);

        $this->assertTrue($result);
    }
}
