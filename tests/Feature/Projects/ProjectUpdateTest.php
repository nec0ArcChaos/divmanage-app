<?php

// ──────────────────────────────────────────────────────────────
// Access & Authorization
// ──────────────────────────────────────────────────────────────

test('guests cannot update a project', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);

    $this->put(route('projects.update', $project), validProjectPayload())
        ->assertRedirect(route('login'));
});

test('users cannot update a project belonging to another workspace', function () {
    [$user] = createWorkspaceUser();
    [$otherUser, $otherWorkspace] = createWorkspaceUser();
    $otherProject = createProject($otherWorkspace, $otherUser);

    $this->actingAs($user)
        ->put(route('projects.update', $otherProject), validProjectPayload())
        ->assertForbidden();
});

// ──────────────────────────────────────────────────────────────
// Successful Update
// ──────────────────────────────────────────────────────────────

test('a project can be updated with valid data', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user, ['name' => 'Old Name', 'status' => 'planning']);

    $this->actingAs($user)
        ->put(route('projects.update', $project), validProjectPayload([
            'name'   => 'Updated Name',
            'status' => 'active',
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('projects.index'));

    expect($project->fresh()->name)->toBe('Updated Name');
    expect($project->fresh()->status)->toBe('active');
});

test('all updatable fields are persisted correctly', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);

    $payload = validProjectPayload([
        'name'        => 'Fully Updated',
        'description' => 'New description.',
        'status'      => 'maintenance',
        'color'       => '#ff0000',
        'start_date'  => '2026-02-01',
        'deadline'    => '2026-11-01',
        'progress'    => 75,
    ]);

    $this->actingAs($user)->put(route('projects.update', $project), $payload);

    $fresh = $project->fresh();
    expect($fresh->name)->toBe('Fully Updated');
    expect($fresh->description)->toBe('New description.');
    expect($fresh->status)->toBe('maintenance');
    expect($fresh->color)->toBe('#ff0000');
    expect($fresh->progress)->toBe(75);
});

test('updating a project redirects to the projects index page', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);

    $this->actingAs($user)
        ->put(route('projects.update', $project), validProjectPayload())
        ->assertRedirect(route('projects.index'));
});

// ──────────────────────────────────────────────────────────────
// Slug Behaviour
// ──────────────────────────────────────────────────────────────

test('the slug remains unchanged when the project name does not change', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user, [
        'name' => 'Original Name',
        'slug' => 'original-name',
    ]);

    $this->actingAs($user)
        ->put(route('projects.update', $project), validProjectPayload([
            'name' => 'Original Name',
        ]));

    expect($project->fresh()->slug)->toBe('original-name');
});

test('the slug is regenerated when the project name changes', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user, [
        'name' => 'Old Name',
        'slug' => 'old-name',
    ]);

    $this->actingAs($user)
        ->put(route('projects.update', $project), validProjectPayload([
            'name' => 'Brand New Name',
        ]));

    expect($project->fresh()->slug)->toBe('brand-new-name');
});

test('renaming to a conflicting name appends a numeric suffix to the new slug', function () {
    [$user, $workspace] = createWorkspaceUser();
    createProject($workspace, $user, ['name' => 'Conflict', 'slug' => 'conflict']);
    $project = createProject($workspace, $user, ['name' => 'Different', 'slug' => 'different']);

    $this->actingAs($user)
        ->put(route('projects.update', $project), validProjectPayload(['name' => 'Conflict']));

    expect($project->fresh()->slug)->toBe('conflict-2');
});

test('renaming a project does not conflict with its own current slug', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user, [
        'name' => 'Keep Me',
        'slug' => 'keep-me',
    ]);

    // Re-saving with the same name must keep the same slug, not add a -2 suffix
    $this->actingAs($user)
        ->put(route('projects.update', $project), validProjectPayload([
            'name' => 'Keep Me',
        ]));

    expect($project->fresh()->slug)->toBe('keep-me');
});

// ──────────────────────────────────────────────────────────────
// Validation
// ──────────────────────────────────────────────────────────────

test('name is required when updating a project', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->put(route('projects.update', $project), validProjectPayload(['name' => '']))
        ->assertSessionHasErrors('name');
});

test('status must be a valid value when updating a project', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->put(route('projects.update', $project), validProjectPayload(['status' => 'unknown']))
        ->assertSessionHasErrors('status');
});

test('color must be a valid hex code when updating a project', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->put(route('projects.update', $project), validProjectPayload(['color' => 'red']))
        ->assertSessionHasErrors('color');
});

test('progress cannot be negative when updating a project', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->put(route('projects.update', $project), validProjectPayload(['progress' => -1]))
        ->assertSessionHasErrors('progress');
});

test('progress cannot exceed 100 when updating a project', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->put(route('projects.update', $project), validProjectPayload(['progress' => 101]))
        ->assertSessionHasErrors('progress');
});

test('deadline must not precede the start date when updating a project', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);

    $this->actingAs($user)
        ->from(route('projects.index'))
        ->put(route('projects.update', $project), validProjectPayload([
            'start_date' => '2026-12-01',
            'deadline'   => '2026-01-01',
        ]))
        ->assertSessionHasErrors('deadline');
});
