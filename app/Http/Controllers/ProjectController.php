<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectStoreRequest;
use App\Http\Requests\ProjectUpdateRequest;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user        = Auth::user();
        $workspaceId = $user->workspace_id;

        $query = Project::where('workspace_id', $workspaceId)
            ->with(['projectMembers.user', 'tasks.status', 'tasks.assignee']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $valid = ['planning', 'active', 'maintenance', 'completed', 'archived'];
            if (in_array($status, $valid)) {
                $query->where('status', $status);
            }
        }

        $sort = $request->input('sort', 'updated');
        if ($sort !== 'progress') {
            match ($sort) {
                'deadline' => $query->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')->orderBy('deadline'),
                'name'     => $query->orderBy('name'),
                default    => $query->orderByDesc('updated_at'),
            };
        }

        $projects = $query->get()->map(function (Project $project) {
            $managerPm = $project->projectMembers->first(fn ($pm) => $pm->role === 'project_manager');

            $members = $project->projectMembers->map(fn ($pm) => [
                'id'       => $pm->user->id,
                'name'     => $pm->user->name,
                'initials' => $this->initials($pm->user->name),
                'role'     => $pm->role,
                'joinedAt' => $pm->joined_at?->format('M d, Y'),
            ])->values()->toArray();

            // Auto-calculate progress from done tasks
            $totalTasks = $project->tasks->count();
            $doneTasks  = $project->tasks->filter(fn ($t) => $t->status && $t->status->is_done)->count();
            $progress   = $totalTasks > 0 ? (int) round(($doneTasks / $totalTasks) * 100) : 0;

            // Status breakdown for mini-bar on cards
            $statusSummary = $project->tasks
                ->groupBy(fn ($t) => $t->status?->name ?? 'No Status')
                ->map(fn ($tasks, $name) => [
                    'name'    => $name,
                    'color'   => $tasks->first()->status?->color ?? '#9ca3af',
                    'is_done' => $tasks->first()->status?->is_done ?? false,
                    'count'   => $tasks->count(),
                ])
                ->sortByDesc('is_done')
                ->values()
                ->toArray();

            // Tasks grouped by assigned member
            $tasksByMember = $project->tasks
                ->groupBy(fn ($t) => $t->assigned_to ?? 0)
                ->map(function ($tasks, $userId) {
                    $user = $tasks->first()->assignee;
                    return [
                        'userId'   => $userId ?: null,
                        'name'     => $user?->name ?? 'Unassigned',
                        'initials' => $user ? $this->initials($user->name) : '??',
                        'tasks'    => $tasks->map(fn ($t) => [
                            'id'       => $t->id,
                            'title'    => $t->title,
                            'priority' => $t->priority,
                            'deadline' => $t->deadline?->format('M d, Y'),
                            'status'   => $t->status ? [
                                'name'    => $t->status->name,
                                'color'   => $t->status->color,
                                'is_done' => $t->status->is_done,
                            ] : null,
                        ])->values()->toArray(),
                    ];
                })
                ->values()
                ->toArray();

            return [
                'id'                => $project->id,
                'name'              => $project->name,
                'slug'              => $project->slug,
                'description'       => $project->description,
                'color'             => $project->color,
                'status'            => $project->status,
                'progress'          => $progress,
                'doneCount'         => $doneTasks,
                'start_date'        => $project->start_date?->format('Y-m-d'),
                'deadline'          => $project->deadline?->format('Y-m-d'),
                'deadlineFormatted' => $project->deadline?->format('M d, Y'),
                'taskCount'         => $totalTasks,
                'statusSummary'     => $statusSummary,
                'tasksByMember'     => $tasksByMember,
                'members'           => $members,
                'manager'           => $managerPm?->user ? [
                    'name'     => $managerPm->user->name,
                    'initials' => $this->initials($managerPm->user->name),
                ] : null,
            ];
        });

        if ($sort === 'progress') {
            $projects = $projects->sortByDesc('progress');
        }

        $projects = $projects->values()->toArray();

        $workspaceUsers = User::where('workspace_id', $workspaceId)
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'id'        => $u->id,
                'name'      => $u->name,
                'initials'  => $this->initials($u->name),
                'job_title' => $u->job_title,
            ])
            ->toArray();

        return Inertia::render('Projects', [
            'projects'       => $projects,
            'stats'          => $this->getStats($workspaceId),
            'filters'        => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', ''),
                'sort'   => $sort,
            ],
            'workspaceUsers' => $workspaceUsers,
        ]);
    }

    public function store(ProjectStoreRequest $request)
    {
        $user        = Auth::user();
        $workspaceId = $user->workspace_id;
        $validated   = $request->validated();

        Project::create([
            'workspace_id' => $workspaceId,
            'name'         => $validated['name'],
            'slug'         => $this->generateSlug($validated['name'], $workspaceId),
            'description'  => $validated['description'] ?? null,
            'color'        => $validated['color'],
            'status'       => $validated['status'],
            'start_date'   => $validated['start_date'] ?: null,
            'deadline'     => $validated['deadline'] ?: null,
            'created_by'   => $user->id,
        ]);

        return redirect()->route('projects.index');
    }

    public function update(ProjectUpdateRequest $request, Project $project)
    {
        $validated = $request->validated();

        $slug = $project->slug;
        if ($project->name !== $validated['name']) {
            $slug = $this->generateSlug($validated['name'], $project->workspace_id, $project->id);
        }

        $project->update([
            'name'        => $validated['name'],
            'slug'        => $slug,
            'description' => $validated['description'] ?? null,
            'color'       => $validated['color'],
            'status'      => $validated['status'],
            'start_date'  => $validated['start_date'] ?: null,
            'deadline'    => $validated['deadline'] ?: null,
        ]);

        return redirect()->route('projects.index');
    }

    private function generateSlug(string $name, int $workspaceId, ?int $excludeId = null): string
    {
        $base   = Str::slug($name);
        $slug   = $base;
        $suffix = 2;

        while (true) {
            $query = Project::where('workspace_id', $workspaceId)->where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            if (! $query->exists()) {
                break;
            }
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function getStats(?int $workspaceId): array
    {
        if ($workspaceId === null) {
            return ['total' => 0, 'active' => 0, 'nearDeadline' => 0, 'completed' => 0];
        }

        $in7Days = Carbon::now()->addDays(7)->toDateString();

        return [
            'total' => Project::where('workspace_id', $workspaceId)->count(),

            'active' => Project::where('workspace_id', $workspaceId)
                ->where('status', 'active')
                ->count(),

            'nearDeadline' => Project::where('workspace_id', $workspaceId)
                ->whereNotIn('status', ['completed', 'archived'])
                ->whereNotNull('deadline')
                ->where('deadline', '<=', $in7Days)
                ->count(),

            'completed' => Project::where('workspace_id', $workspaceId)
                ->where('status', 'completed')
                ->count(),
        ];
    }

    private function initials(string $name): string
    {
        $parts = explode(' ', trim($name));
        if (count($parts) >= 2) {
            return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
        }

        return strtoupper(mb_substr($name, 0, 2));
    }
}
