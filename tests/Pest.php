<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create a workspace and a user assigned to it.
 *
 * @return array{0: \App\Models\User, 1: \App\Models\Workspace}
 */
function createWorkspaceUser(array $userAttrs = []): array
{
    $owner = \App\Models\User::factory()->create();

    $workspace = \App\Models\Workspace::create([
        'name'        => 'Test Workspace',
        'slug'        => 'ws-' . uniqid(),
        'timezone'    => 'Asia/Jakarta',
        'date_format' => 'DD/MM/YYYY',
        'owner_id'    => $owner->id,
    ]);

    $user = \App\Models\User::factory()->create(array_merge(
        ['workspace_id' => $workspace->id],
        $userAttrs,
    ));

    return [$user, $workspace];
}

/**
 * Create a project inside the given workspace.
 * Any Project fillable attribute can be overridden via $attrs.
 */
function createProject(\App\Models\Workspace $workspace, \App\Models\User $creator, array $attrs = []): \App\Models\Project
{
    $name = $attrs['name'] ?? ('Project ' . uniqid());

    return \App\Models\Project::create(array_merge([
        'workspace_id' => $workspace->id,
        'name'         => $name,
        'slug'         => \Illuminate\Support\Str::slug($name) . '-' . uniqid(),
        'color'        => '#3b6ff8',
        'status'       => 'active',
        'progress'     => 0,
        'created_by'   => $creator->id,
    ], $attrs));
}

/**
 * Add a user to a project as a member with the given role.
 */
function addProjectMember(\App\Models\Project $project, \App\Models\User $user, string $role = 'developer'): \App\Models\ProjectMember
{
    return \App\Models\ProjectMember::create([
        'project_id' => $project->id,
        'user_id'    => $user->id,
        'role'       => $role,
        'joined_at'  => now(),
    ]);
}

/**
 * Returns a minimal valid payload for creating or updating a project.
 */
function validProjectPayload(array $overrides = []): array
{
    return array_merge([
        'name'        => 'New Test Project',
        'description' => 'A test project description.',
        'status'      => 'planning',
        'color'       => '#3b6ff8',
        'start_date'  => '2026-01-01',
        'deadline'    => '2026-12-31',
        'progress'    => 0,
    ], $overrides);
}
