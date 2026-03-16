<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// ──────────────────────────────────────────────────────────────
// Access Control
// ──────────────────────────────────────────────────────────────

test('guests are redirected to the login page', function () {
    $this->get(route('projects.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can visit the projects page', function () {
    [$user] = createWorkspaceUser();

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertOk();
});

test('projects page renders the Projects Inertia component with required props', function () {
    [$user] = createWorkspaceUser();

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects')
            ->has('projects')
            ->has('stats')
            ->has('filters')
            ->has('workspaceUsers')
        );
});

// ──────────────────────────────────────────────────────────────
// Workspace Isolation
// ──────────────────────────────────────────────────────────────

test('only projects from the authenticated user\'s workspace are returned', function () {
    [$user, $workspace] = createWorkspaceUser();
    createProject($workspace, $user);
    createProject($workspace, $user);

    // Project in a separate workspace — must not appear
    [$otherUser, $otherWorkspace] = createWorkspaceUser();
    createProject($otherWorkspace, $otherUser);

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('projects', 2)
        );
});

test('projects from other workspaces are never included in the response', function () {
    [$user] = createWorkspaceUser();

    [$otherUser, $otherWorkspace] = createWorkspaceUser();
    createProject($otherWorkspace, $otherUser);

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('projects', 0)
        );
});

test('user without a workspace sees an empty project list and zero stats', function () {
    $user = User::factory()->create(); // no workspace_id set

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('projects', 0)
            ->where('stats.total', 0)
            ->where('stats.active', 0)
            ->where('stats.nearDeadline', 0)
            ->where('stats.completed', 0)
        );
});

// ──────────────────────────────────────────────────────────────
// workspaceUsers Prop
// ──────────────────────────────────────────────────────────────

test('workspaceUsers contains only members of the authenticated user\'s workspace', function () {
    [$user, $workspace] = createWorkspaceUser();
    User::factory()->count(2)->create(['workspace_id' => $workspace->id]);

    // User in a different workspace — must not appear
    createWorkspaceUser();

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('workspaceUsers', 3) // $user + 2 extras
        );
});

test('each workspaceUser entry contains the expected fields', function () {
    [$user] = createWorkspaceUser();

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('workspaceUsers.0', fn (Assert $u) => $u
                ->has('id')
                ->has('name')
                ->has('initials')
                ->has('job_title')
            )
        );
});

// ──────────────────────────────────────────────────────────────
// Project Members in Response
// ──────────────────────────────────────────────────────────────

test('each project includes its list of members', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);
    addProjectMember($project, $user, 'developer');

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('projects', 1)
            ->has('projects.0.members', 1)
            ->where('projects.0.members.0.role', 'developer')
        );
});

test('each member entry contains id, name, initials, role, and joinedAt', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);
    addProjectMember($project, $user, 'qa');

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('projects.0.members.0', fn (Assert $m) => $m
                ->has('id')
                ->has('name')
                ->has('initials')
                ->has('role')
                ->has('joinedAt')
            )
        );
});

test('project with a project_manager member exposes manager info', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);
    addProjectMember($project, $user, 'project_manager');

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('projects.0.manager')
            ->has('projects.0.manager.name')
            ->has('projects.0.manager.initials')
        );
});

test('project with no project_manager has a null manager field', function () {
    [$user, $workspace] = createWorkspaceUser();
    $project = createProject($workspace, $user);
    addProjectMember($project, $user, 'developer'); // not a manager

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('projects.0.manager', null)
        );
});

// ──────────────────────────────────────────────────────────────
// Search Filter
// ──────────────────────────────────────────────────────────────

test('search by project name filters the results', function () {
    [$user, $workspace] = createWorkspaceUser();
    createProject($workspace, $user, ['name' => 'Alpha System']);
    createProject($workspace, $user, ['name' => 'Beta Service']);
    createProject($workspace, $user, ['name' => 'Gamma Platform']);

    $this->actingAs($user)
        ->get(route('projects.index', ['search' => 'alpha']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('projects', 1)
            ->where('projects.0.name', 'Alpha System')
        );
});

test('search by project description filters the results', function () {
    [$user, $workspace] = createWorkspaceUser();
    createProject($workspace, $user, ['description' => 'handles user authentication flows']);
    createProject($workspace, $user, ['description' => 'unrelated feature module']);

    $this->actingAs($user)
        ->get(route('projects.index', ['search' => 'authentication']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('projects', 1)
        );
});

test('search with no matching term returns an empty list', function () {
    [$user, $workspace] = createWorkspaceUser();
    createProject($workspace, $user, ['name' => 'Known Project']);

    $this->actingAs($user)
        ->get(route('projects.index', ['search' => 'nonexistent-xyz-000']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('projects', 0)
        );
});

test('the applied search term is reflected in the filters prop', function () {
    [$user] = createWorkspaceUser();

    $this->actingAs($user)
        ->get(route('projects.index', ['search' => 'my query']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.search', 'my query')
        );
});

// ──────────────────────────────────────────────────────────────
// Status Filter
// ──────────────────────────────────────────────────────────────

test('status filter returns only projects with the matching status', function () {
    [$user, $workspace] = createWorkspaceUser();
    createProject($workspace, $user, ['status' => 'active']);
    createProject($workspace, $user, ['status' => 'active']);
    createProject($workspace, $user, ['status' => 'planning']);
    createProject($workspace, $user, ['status' => 'completed']);

    $this->actingAs($user)
        ->get(route('projects.index', ['status' => 'active']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('projects', 2)
        );
});

test('an invalid status value is ignored and all projects are returned', function () {
    [$user, $workspace] = createWorkspaceUser();
    createProject($workspace, $user);
    createProject($workspace, $user);

    $this->actingAs($user)
        ->get(route('projects.index', ['status' => 'bogus-status']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('projects', 2)
        );
});

test('the applied status filter is reflected in the filters prop', function () {
    [$user] = createWorkspaceUser();

    $this->actingAs($user)
        ->get(route('projects.index', ['status' => 'active']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.status', 'active')
        );
});

// ──────────────────────────────────────────────────────────────
// Sort
// ──────────────────────────────────────────────────────────────

test('sort by name orders projects alphabetically ascending', function () {
    [$user, $workspace] = createWorkspaceUser();
    createProject($workspace, $user, ['name' => 'Zebra Project']);
    createProject($workspace, $user, ['name' => 'Apple Project']);
    createProject($workspace, $user, ['name' => 'Mango Project']);

    $this->actingAs($user)
        ->get(route('projects.index', ['sort' => 'name']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('projects.0.name', 'Apple Project')
            ->where('projects.1.name', 'Mango Project')
            ->where('projects.2.name', 'Zebra Project')
        );
});

test('sort by progress orders projects from highest to lowest', function () {
    [$user, $workspace] = createWorkspaceUser();
    createProject($workspace, $user, ['progress' => 10]);
    createProject($workspace, $user, ['progress' => 90]);
    createProject($workspace, $user, ['progress' => 50]);

    $this->actingAs($user)
        ->get(route('projects.index', ['sort' => 'progress']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('projects.0.progress', 90)
            ->where('projects.1.progress', 50)
            ->where('projects.2.progress', 10)
        );
});

test('sort by deadline orders by soonest deadline first with null deadlines last', function () {
    [$user, $workspace] = createWorkspaceUser();
    createProject($workspace, $user, ['deadline' => '2026-06-01']);
    createProject($workspace, $user, ['deadline' => null]);
    createProject($workspace, $user, ['deadline' => '2026-03-01']);

    $this->actingAs($user)
        ->get(route('projects.index', ['sort' => 'deadline']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('projects.0.deadline', '2026-03-01')
            ->where('projects.1.deadline', '2026-06-01')
            ->where('projects.2.deadline', null)
        );
});

test('the applied sort is reflected in the filters prop', function () {
    [$user] = createWorkspaceUser();

    $this->actingAs($user)
        ->get(route('projects.index', ['sort' => 'name']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.sort', 'name')
        );
});

// ──────────────────────────────────────────────────────────────
// Stats
// ──────────────────────────────────────────────────────────────

test('stats.total counts all workspace projects regardless of status', function () {
    [$user, $workspace] = createWorkspaceUser();
    createProject($workspace, $user, ['status' => 'active']);
    createProject($workspace, $user, ['status' => 'planning']);
    createProject($workspace, $user, ['status' => 'completed']);

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.total', 3)
        );
});

test('stats.active counts only projects with active status', function () {
    [$user, $workspace] = createWorkspaceUser();
    createProject($workspace, $user, ['status' => 'active']);
    createProject($workspace, $user, ['status' => 'active']);
    createProject($workspace, $user, ['status' => 'planning']);

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.active', 2)
        );
});

test('stats.nearDeadline counts non-completed non-archived projects with deadline within 7 days', function () {
    [$user, $workspace] = createWorkspaceUser();

    // Should count — deadline is today
    createProject($workspace, $user, ['deadline' => now()->toDateString(), 'status' => 'active']);
    // Should count — deadline in 6 days
    createProject($workspace, $user, ['deadline' => now()->addDays(6)->toDateString(), 'status' => 'active']);

    // Should NOT count — deadline 8 days away
    createProject($workspace, $user, ['deadline' => now()->addDays(8)->toDateString(), 'status' => 'active']);
    // Should NOT count — completed status is excluded
    createProject($workspace, $user, ['deadline' => now()->addDays(1)->toDateString(), 'status' => 'completed']);
    // Should NOT count — archived status is excluded
    createProject($workspace, $user, ['deadline' => now()->addDays(1)->toDateString(), 'status' => 'archived']);
    // Should NOT count — no deadline set
    createProject($workspace, $user, ['deadline' => null, 'status' => 'active']);

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.nearDeadline', 2)
        );
});

test('stats.completed counts only projects with completed status', function () {
    [$user, $workspace] = createWorkspaceUser();
    createProject($workspace, $user, ['status' => 'completed']);
    createProject($workspace, $user, ['status' => 'completed']);
    createProject($workspace, $user, ['status' => 'active']);

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.completed', 2)
        );
});

test('stats are scoped to the authenticated user\'s workspace only', function () {
    [$user, $workspace] = createWorkspaceUser();
    [$otherUser, $otherWorkspace] = createWorkspaceUser();

    createProject($workspace, $user, ['status' => 'active']);
    createProject($otherWorkspace, $otherUser, ['status' => 'active']); // must not inflate stats

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.total', 1)
            ->where('stats.active', 1)
        );
});
