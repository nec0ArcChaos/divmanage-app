<?php

use App\Models\ProjectMember;
use App\Models\User;

// ══════════════════════════════════════════════════════════════
// STORE — Add a Member
// ══════════════════════════════════════════════════════════════

test('guests cannot add a member to a project', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);

    $this->post(route('project-members.store', $project), [
        'user_id' => $user->id,
        'role'    => 'developer',
    ])->assertRedirect(route('login'));
});

test('users cannot add members to a project in another workspace', function () {
    [$user] = createWorkspaceUser();
    [$otherUser, $otherWorkspace] = createWorkspaceUser();
    $otherProject = createProject($otherWorkspace, $otherUser);

    $this->actingAs($user)
        ->post(route('project-members.store', $otherProject), [
            'user_id' => $otherUser->id,
            'role'    => 'developer',
        ])
        ->assertForbidden();
});

test('a workspace member can be added to a project', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project   = createProject($workspace, $user);
    $newMember = User::factory()->create(['workspace_id' => $workspace->id]);

    $this->actingAs($user)
        ->post(route('project-members.store', $project), [
            'user_id' => $newMember->id,
            'role'    => 'developer',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('projects.index'));

    $this->assertDatabaseHas('project_members', [
        'project_id' => $project->id,
        'user_id'    => $newMember->id,
        'role'       => 'developer',
    ]);
});

test('the joined_at timestamp is set when a member is added', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project   = createProject($workspace, $user);
    $newMember = User::factory()->create(['workspace_id' => $workspace->id]);

    $this->actingAs($user)
        ->post(route('project-members.store', $project), [
            'user_id' => $newMember->id,
            'role'    => 'developer',
        ]);

    $record = ProjectMember::where('project_id', $project->id)
        ->where('user_id', $newMember->id)
        ->first();

    expect($record->joined_at)->not->toBeNull();
});

test('every accepted role can be assigned to a new member', function (string $role) {
    [$user, $workspace] = createWorkspaceUser();
    $project   = createProject($workspace, $user);
    $newMember = User::factory()->create(['workspace_id' => $workspace->id]);

    $this->actingAs($user)
        ->post(route('project-members.store', $project), [
            'user_id' => $newMember->id,
            'role'    => $role,
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('project_members', [
        'project_id' => $project->id,
        'user_id'    => $newMember->id,
        'role'       => $role,
    ]);
})->with(['project_manager', 'developer', 'qa', 'designer', 'viewer']);

test('adding a member redirects to the projects index page', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project   = createProject($workspace, $user);
    $newMember = User::factory()->create(['workspace_id' => $workspace->id]);

    $this->actingAs($user)
        ->post(route('project-members.store', $project), [
            'user_id' => $newMember->id,
            'role'    => 'developer',
        ])
        ->assertRedirect(route('projects.index'));
});

// ── Cross-workspace Guard ────────────────────────────────────

test('a user from a different workspace cannot be added as a member', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);

    [$outsider] = createWorkspaceUser(); // belongs to a different workspace

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->post(route('project-members.store', $project), [
            'user_id' => $outsider->id,
            'role'    => 'developer',
        ])
        ->assertSessionHasErrors('user_id');

    $this->assertDatabaseMissing('project_members', [
        'project_id' => $project->id,
        'user_id'    => $outsider->id,
    ]);
});

// ── Duplicate Guard ──────────────────────────────────────────

test('a user cannot be added to the same project twice', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);
    addProjectMember($project, $user, 'developer');

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->post(route('project-members.store', $project), [
            'user_id' => $user->id,
            'role'    => 'qa',
        ])
        ->assertSessionHasErrors('user_id');

    expect(
        ProjectMember::where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->count()
    )->toBe(1);
});

// ── Validation ───────────────────────────────────────────────

test('user_id is required when adding a member', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->post(route('project-members.store', $project), ['role' => 'developer'])
        ->assertSessionHasErrors('user_id');
});

test('user_id must reference an existing user', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->post(route('project-members.store', $project), [
            'user_id' => 99999,
            'role'    => 'developer',
        ])
        ->assertSessionHasErrors('user_id');
});

test('role is required when adding a member', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project   = createProject($workspace, $user);
    $newMember = User::factory()->create(['workspace_id' => $workspace->id]);

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->post(route('project-members.store', $project), ['user_id' => $newMember->id])
        ->assertSessionHasErrors('role');
});

test('role must be one of the accepted values when adding a member', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project   = createProject($workspace, $user);
    $newMember = User::factory()->create(['workspace_id' => $workspace->id]);

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->post(route('project-members.store', $project), [
            'user_id' => $newMember->id,
            'role'    => 'superadmin',
        ])
        ->assertSessionHasErrors('role');
});

// ══════════════════════════════════════════════════════════════
// UPDATE — Change a Member's Role
// ══════════════════════════════════════════════════════════════

test('guests cannot update a project member\'s role', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);
    addProjectMember($project, $user, 'developer');

    $this->put(route('project-members.update', [$project, $user]), ['role' => 'qa'])
        ->assertRedirect(route('login'));
});

test('users cannot update a member\'s role in another workspace\'s project', function () {
    [$user] = createWorkspaceUser();
    [$otherUser, $otherWorkspace] = createWorkspaceUser();
    $otherProject = createProject($otherWorkspace, $otherUser);
    addProjectMember($otherProject, $otherUser, 'developer');

    $this->actingAs($user)
        ->put(route('project-members.update', [$otherProject, $otherUser]), ['role' => 'qa'])
        ->assertForbidden();
});

test('a project member\'s role can be updated', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);
    addProjectMember($project, $user, 'developer');

    $this->actingAs($user)
        ->put(route('project-members.update', [$project, $user]), ['role' => 'qa'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('projects.index'));

    $this->assertDatabaseHas('project_members', [
        'project_id' => $project->id,
        'user_id'    => $user->id,
        'role'       => 'qa',
    ]);
});

test('every accepted role value can be assigned via update', function (string $role) {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);
    addProjectMember($project, $user, 'developer');

    $this->actingAs($user)
        ->put(route('project-members.update', [$project, $user]), ['role' => $role])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('project_members', [
        'project_id' => $project->id,
        'user_id'    => $user->id,
        'role'       => $role,
    ]);
})->with(['project_manager', 'developer', 'qa', 'designer', 'viewer']);

test('updating a member\'s role redirects to the projects index page', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);
    addProjectMember($project, $user, 'developer');

    $this->actingAs($user)
        ->put(route('project-members.update', [$project, $user]), ['role' => 'designer'])
        ->assertRedirect(route('projects.index'));
});

test('role is required when updating a member', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);
    addProjectMember($project, $user, 'developer');

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->put(route('project-members.update', [$project, $user]), ['role' => ''])
        ->assertSessionHasErrors('role');
});

test('an invalid role value fails validation when updating a member', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);
    addProjectMember($project, $user, 'developer');

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->put(route('project-members.update', [$project, $user]), ['role' => 'not-a-role'])
        ->assertSessionHasErrors('role');
});

test('updating a role returns 404 if the user is not a member of the project', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project   = createProject($workspace, $user);
    $nonMember = User::factory()->create(['workspace_id' => $workspace->id]);

    $this->actingAs($user)
        ->put(route('project-members.update', [$project, $nonMember]), ['role' => 'qa'])
        ->assertNotFound();
});

// ══════════════════════════════════════════════════════════════
// DESTROY — Remove a Member
// ══════════════════════════════════════════════════════════════

test('guests cannot remove a member from a project', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);
    addProjectMember($project, $user, 'developer');

    $this->delete(route('project-members.destroy', [$project, $user]))
        ->assertRedirect(route('login'));
});

test('users cannot remove a member from another workspace\'s project', function () {
    [$user] = createWorkspaceUser();
    [$otherUser, $otherWorkspace] = createWorkspaceUser();
    $otherProject = createProject($otherWorkspace, $otherUser);
    addProjectMember($otherProject, $otherUser, 'developer');

    $this->actingAs($user)
        ->delete(route('project-members.destroy', [$otherProject, $otherUser]))
        ->assertForbidden();
});

test('a member can be removed from a project', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);
    addProjectMember($project, $user, 'developer');

    $this->actingAs($user)
        ->delete(route('project-members.destroy', [$project, $user]))
        ->assertRedirect(route('projects.index'));

    $this->assertDatabaseMissing('project_members', [
        'project_id' => $project->id,
        'user_id'    => $user->id,
    ]);
});

test('removing a member does not affect other members of the same project', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project   = createProject($workspace, $user);
    $otherUser = User::factory()->create(['workspace_id' => $workspace->id]);
    addProjectMember($project, $user, 'developer');
    addProjectMember($project, $otherUser, 'qa');

    $this->actingAs($user)
        ->delete(route('project-members.destroy', [$project, $user]));

    // otherUser must still be a member
    $this->assertDatabaseHas('project_members', [
        'project_id' => $project->id,
        'user_id'    => $otherUser->id,
    ]);
});

test('removing a member redirects to the projects index page', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);
    addProjectMember($project, $user, 'developer');

    $this->actingAs($user)
        ->delete(route('project-members.destroy', [$project, $user]))
        ->assertRedirect(route('projects.index'));
});

test('removing a non-member returns a 404', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project   = createProject($workspace, $user);
    $nonMember = User::factory()->create(['workspace_id' => $workspace->id]);

    $this->actingAs($user)
        ->delete(route('project-members.destroy', [$project, $nonMember]))
        ->assertNotFound();
});
