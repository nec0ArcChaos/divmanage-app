<?php

use App\Models\User;

// ──────────────────────────────────────────────────────────────
// Access & Authorization
// ──────────────────────────────────────────────────────────────

test('guests cannot create a project', function () {
    $this->post(route('projects.store'), validProjectPayload())
        ->assertRedirect(route('login'));
});

test('users without a workspace cannot create a project', function () {
    $user = User::factory()->create(); // no workspace_id

    $this->actingAs($user)
        ->post(route('projects.store'), validProjectPayload())
        ->assertForbidden();
});

// ──────────────────────────────────────────────────────────────
// Successful Creation
// ──────────────────────────────────────────────────────────────

test('a project can be created with valid data', function () {
    [$user] = createWorkspaceUser();

    $this->actingAs($user)
        ->post(route('projects.store'), validProjectPayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('projects.index'));

    $this->assertDatabaseHas('projects', ['name' => 'New Test Project']);
});

test('the created project is assigned to the authenticated user\'s workspace', function () {
    [$user, $workspace] = createWorkspaceUser();

    $this->actingAs($user)
        ->post(route('projects.store'), validProjectPayload(['name' => 'Workspace Project']));

    $this->assertDatabaseHas('projects', [
        'name'         => 'Workspace Project',
        'workspace_id' => $workspace->id,
    ]);
});

test('the authenticated user is recorded as the project creator', function () {
    [$user] = createWorkspaceUser();

    $this->actingAs($user)
        ->post(route('projects.store'), validProjectPayload(['name' => 'Creator Project']));

    $this->assertDatabaseHas('projects', [
        'name'       => 'Creator Project',
        'created_by' => $user->id,
    ]);
});

test('a slug is generated automatically from the project name', function () {
    [$user] = createWorkspaceUser();

    $this->actingAs($user)
        ->post(route('projects.store'), validProjectPayload(['name' => 'My Awesome Project']));

    $this->assertDatabaseHas('projects', ['slug' => 'my-awesome-project']);
});

test('a duplicate project name in the same workspace gets a numeric suffix on the slug', function () {
    [$user, $workspace] = createWorkspaceUser();

    // Pre-occupy the base slug
    createProject($workspace, $user, ['name' => 'Duplicate Project', 'slug' => 'duplicate-project']);

    $this->actingAs($user)
        ->post(route('projects.store'), validProjectPayload(['name' => 'Duplicate Project']));

    $this->assertDatabaseHas('projects', ['slug' => 'duplicate-project-2']);
});

test('a third duplicate increments the suffix correctly', function () {
    [$user, $workspace] = createWorkspaceUser();

    createProject($workspace, $user, ['slug' => 'same-name']);
    createProject($workspace, $user, ['slug' => 'same-name-2']);

    $this->actingAs($user)
        ->post(route('projects.store'), validProjectPayload(['name' => 'Same Name']));

    $this->assertDatabaseHas('projects', ['slug' => 'same-name-3']);
});

test('creating a project redirects to the projects index page', function () {
    [$user] = createWorkspaceUser();

    $this->actingAs($user)
        ->post(route('projects.store'), validProjectPayload())
        ->assertRedirect(route('projects.index'));
});

// ──────────────────────────────────────────────────────────────
// Validation — Required Fields
// ──────────────────────────────────────────────────────────────

test('name is required', function () {
    [$user] = createWorkspaceUser();

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->post(route('projects.store'), validProjectPayload(['name' => '']))
        ->assertSessionHasErrors('name');
});

test('status is required', function () {
    [$user] = createWorkspaceUser();

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->post(route('projects.store'), validProjectPayload(['status' => '']))
        ->assertSessionHasErrors('status');
});

test('color is required', function () {
    [$user] = createWorkspaceUser();

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->post(route('projects.store'), validProjectPayload(['color' => '']))
        ->assertSessionHasErrors('color');
});

test('progress is required', function () {
    [$user] = createWorkspaceUser();

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->post(route('projects.store'), validProjectPayload(['progress' => '']))
        ->assertSessionHasErrors('progress');
});

// ──────────────────────────────────────────────────────────────
// Validation — Field Constraints
// ──────────────────────────────────────────────────────────────

test('status must be one of the accepted enum values', function () {
    [$user] = createWorkspaceUser();

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->post(route('projects.store'), validProjectPayload(['status' => 'invalid-status']))
        ->assertSessionHasErrors('status');
});

test('color must be a valid 6-digit hex code', function () {
    [$user] = createWorkspaceUser();

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->post(route('projects.store'), validProjectPayload(['color' => 'not-a-color']))
        ->assertSessionHasErrors('color');
});

test('color with a short hex code fails validation', function () {
    [$user] = createWorkspaceUser();

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->post(route('projects.store'), validProjectPayload(['color' => '#abc']))
        ->assertSessionHasErrors('color');
});

test('progress must not be less than 0', function () {
    [$user] = createWorkspaceUser();

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->post(route('projects.store'), validProjectPayload(['progress' => -1]))
        ->assertSessionHasErrors('progress');
});

test('progress must not exceed 100', function () {
    [$user] = createWorkspaceUser();

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->post(route('projects.store'), validProjectPayload(['progress' => 101]))
        ->assertSessionHasErrors('progress');
});

test('deadline must be on or after the start date', function () {
    [$user] = createWorkspaceUser();

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->post(route('projects.store'), validProjectPayload([
            'start_date' => '2026-12-31',
            'deadline'   => '2026-01-01',
        ]))
        ->assertSessionHasErrors('deadline');
});

test('start date must be before or equal to the deadline', function () {
    [$user] = createWorkspaceUser();

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->post(route('projects.store'), validProjectPayload([
            'start_date' => '2026-06-01',
            'deadline'   => '2026-05-01',
        ]))
        ->assertSessionHasErrors('start_date');
});

// ──────────────────────────────────────────────────────────────
// Optional Fields
// ──────────────────────────────────────────────────────────────

test('a project can be created without optional fields', function () {
    [$user] = createWorkspaceUser();

    $this->actingAs($user)
        ->post(route('projects.store'), [
            'name'        => 'Minimal Project',
            'description' => null,
            'status'      => 'planning',
            'color'       => '#aabbcc',
            'start_date'  => null,
            'deadline'    => null,
            'progress'    => 0,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('projects.index'));

    $this->assertDatabaseHas('projects', ['name' => 'Minimal Project']);
});

test('description is optional and can be omitted', function () {
    [$user] = createWorkspaceUser();

    $payload = validProjectPayload();
    unset($payload['description']);

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->post(route('projects.store'), $payload)
        ->assertSessionHasNoErrors();
});
