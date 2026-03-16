<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskStoreRequest;
use App\Http\Requests\TaskUpdateRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TaskController extends Controller
{
    public function index()
    {
        $user        = Auth::user();
        $workspaceId = $user->workspace_id;

        $tasks = Task::where('assigned_to', $user->id)
            ->with(['project', 'status', 'assignee', 'creator'])
            ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')
            ->orderBy('deadline')
            ->get()
            ->map(function (Task $task) use ($user) {
                return [
                    'id'                => $task->id,
                    'title'             => $task->title,
                    'description'       => $task->description,
                    'priority'          => $task->priority,
                    'deadline'          => $task->deadline?->format('Y-m-d'),
                    'deadlineFormatted' => $task->deadline?->format('M d, Y'),
                    'project_id'        => $task->project_id,
                    'task_status_id'    => $task->task_status_id,
                    'project'           => [
                        'id'    => $task->project->id,
                        'name'  => $task->project->name,
                        'color' => $task->project->color,
                    ],
                    'status'            => [
                        'id'      => $task->status->id,
                        'name'    => $task->status->name,
                        'slug'    => $task->status->slug,
                        'color'   => $task->status->color,
                        'is_done' => $task->status->is_done,
                    ],
                    'assignee'          => $task->assignee ? [
                        'id'   => $task->assignee->id,
                        'name' => $task->assignee->name,
                    ] : null,
                    'updated_at'           => $task->updated_at->format('Y-m-d H:i:s'),
                    'completed_at'         => $task->completed_at?->format('Y-m-d'),
                    'completedAtFormatted' => $task->completed_at?->format('M d, Y'),
                    'canDelete'            => $task->created_by === $user->id,
                    'createdByName'        => $task->creator?->name ?? null,
                    'assignedAt'           => $task->created_at?->format('M d, Y'),
                ];
            })
            ->values()
            ->toArray();

        $taskStatuses = TaskStatus::where('workspace_id', $workspaceId)
            ->orderBy('position')
            ->get(['id', 'name', 'slug', 'color', 'is_done'])
            ->toArray();

        $projects = Project::where('workspace_id', $workspaceId)
            ->orderBy('name')
            ->get(['id', 'name', 'color'])
            ->toArray();

        return Inertia::render('MyTasks', [
            'tasks'       => $tasks,
            'stats'       => $this->getStats($user->id),
            'taskStatuses' => $taskStatuses,
            'projects'    => $projects,
            'today'       => now()->toDateString(),
        ]);
    }

    public function store(TaskStoreRequest $request)
    {
        $user      = Auth::user();
        $validated = $request->validated();

        Project::where('id', $validated['project_id'])
            ->where('workspace_id', $user->workspace_id)
            ->firstOrFail();

        $status = TaskStatus::find($validated['task_status_id']);

        Task::create([
            'project_id'     => $validated['project_id'],
            'task_status_id' => $validated['task_status_id'],
            'title'          => $validated['title'],
            'description'    => $validated['description'] ?? null,
            'priority'       => $validated['priority'],
            'assigned_to'    => $user->id,
            'created_by'     => $user->id,
            'deadline'       => $validated['deadline'] ?: null,
            'completed_at'   => $status->is_done ? now() : null,
        ]);

        return redirect()->route('tasks.index');
    }

    public function update(TaskUpdateRequest $request, Task $task)
    {
        $validated = $request->validated();

        Project::where('id', $validated['project_id'])
            ->where('workspace_id', Auth::user()->workspace_id)
            ->firstOrFail();

        $newStatus  = TaskStatus::find($validated['task_status_id']);
        $updateData = [
            'project_id'     => $validated['project_id'],
            'task_status_id' => $validated['task_status_id'],
            'title'          => $validated['title'],
            'description'    => $validated['description'] ?? null,
            'priority'       => $validated['priority'],
            'deadline'       => $validated['deadline'] ?: null,
        ];

        if ($newStatus->is_done && ! $task->completed_at) {
            $updateData['completed_at'] = now();
        } elseif (! $newStatus->is_done) {
            $updateData['completed_at'] = null;
        }

        $task->update($updateData);

        return redirect()->route('tasks.index');
    }

    public function destroy(Task $task)
    {
        if ($task->created_by !== Auth::id()) {
            abort(403);
        }

        $task->delete();

        return redirect()->route('tasks.index');
    }

    public function updateStatus(Request $request, Task $task): RedirectResponse
    {
        if ($task->assigned_to !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'task_status_id' => ['required', 'integer', 'exists:task_statuses,id'],
        ]);

        $newStatus  = TaskStatus::find($validated['task_status_id']);
        $updateData = ['task_status_id' => $validated['task_status_id']];

        if ($newStatus->is_done && ! $task->completed_at) {
            $updateData['completed_at'] = now();
        } elseif (! $newStatus->is_done) {
            $updateData['completed_at'] = null;
        }

        $task->update($updateData);

        return redirect()->route('tasks.index');
    }

    private function getStats(int $userId): array
    {
        $today = now()->toDateString();

        return [
            'assigned' => Task::where('assigned_to', $userId)->count(),

            'inProgress' => Task::where('assigned_to', $userId)
                ->whereHas('status', fn ($q) => $q->where('is_done', false)->where('slug', '!=', 'backlog'))
                ->count(),

            'overdue' => Task::where('assigned_to', $userId)
                ->whereNotNull('deadline')
                ->where('deadline', '<', $today)
                ->whereHas('status', fn ($q) => $q->where('is_done', false))
                ->count(),

            'completed' => Task::where('assigned_to', $userId)
                ->whereHas('status', fn ($q) => $q->where('is_done', true))
                ->count(),
        ];
    }
}
