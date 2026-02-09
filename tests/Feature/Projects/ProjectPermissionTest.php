<?php

namespace Tests\Feature\Projects;

use Tests\TestCase;
use App\Models\User;
use App\Models\OldProjects\Project;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ProjectPermissionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function owner_can_edit_own_project()
    {
        $user = User::factory()->create(['role' => 'executor']);
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)
            ->get("/projects/{$project->project_id}/edit");

        $response->assertStatus(200);
    }

    /** @test */
    public function non_owner_cannot_edit_project()
    {
        $owner = User::factory()->create(['role' => 'executor']);
        $otherUser = User::factory()->create(['role' => 'executor']);
        $project = Project::factory()->create([
            'user_id' => $owner->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($otherUser)
            ->get("/projects/{$project->project_id}/edit");

        $response->assertStatus(403);
    }

    /** @test */
    public function user_cannot_edit_submitted_project()
    {
        $user = User::factory()->create(['role' => 'executor']);
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'status' => 'submitted_to_provincial',
        ]);

        $response = $this->actingAs($user)
            ->get("/projects/{$project->project_id}/edit");

        $response->assertStatus(403);
    }

    /** @test */
    public function coordinator_can_view_any_project()
    {
        $coordinator = User::factory()->create(['role' => 'coordinator']);
        $project = Project::factory()->create();

        $response = $this->actingAs($coordinator)
            ->get("/projects/{$project->project_id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function owner_can_view_own_project()
    {
        $user = User::factory()->create(['role' => 'executor']);
        $project = Project::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get("/projects/{$project->project_id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function non_owner_cannot_view_others_project()
    {
        $owner = User::factory()->create(['role' => 'executor']);
        $otherUser = User::factory()->create(['role' => 'executor']);
        $project = Project::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->get("/projects/{$project->project_id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function executor_can_create_project()
    {
        $user = User::factory()->create(['role' => 'executor']);

        $response = $this->actingAs($user)
            ->get('/projects/create');

        $response->assertStatus(200);
    }

    /** @test */
    public function applicant_can_create_project()
    {
        $user = User::factory()->create(['role' => 'applicant']);

        $response = $this->actingAs($user)
            ->get('/projects/create');

        $response->assertStatus(200);
    }

    /** @test */
    public function unauthorized_user_cannot_access_projects()
    {
        $response = $this->get('/projects');

        $response->assertRedirect('/login');
    }
}
