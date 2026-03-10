<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user        = Auth::user();
        $workspaceId = $user->workspace_id;

        $doneStatusIds = TaskStatus::where('workspace_id', $workspaceId)
            ->where('is_done', true)
            ->pluck('id');

        $inProgressId = TaskStatus::where('workspace_id', $workspaceId)
            ->where('slug', 'in_progress')
            ->value('id');

        return Inertia::render('Dashboard', [
            'stats'          => $this->getStats($workspaceId, $doneStatusIds, $inProgressId),
            'recentProjects' => $this->getRecentProjects($workspaceId),
            'myTasks'        => $this->getMyTasks($user->id, $doneStatusIds),
            'recentActivity' => $this->getRecentActivity($workspaceId),
        ]);
    }

    private function getStats(int $workspaceId, $doneStatusIds, ?int $inProgressId): array
    {
        $now       = Carbon::now();
        $lastWeek  = $now->copy()->subWeek();
        $twoWeeks  = $now->copy()->subWeeks(2);

        // Active projects (current)
        $activeProjects     = Project::where('workspace_id', $workspaceId)
            ->where('status', 'active')->count();
        $activeProjectsPrev = Project::where('workspace_id', $workspaceId)
            ->where('status', 'active')
            ->where('created_at', '<', $lastWeek)->count();

        // Tasks in progress
        $tasksInProgress = $inProgressId
            ? Task::whereHas('project', fn ($q) => $q->where('workspace_id', $workspaceId))
                ->where('task_status_id', $inProgressId)
                ->count()
            : 0;
        $tasksInProgressPrev = $inProgressId
            ? Task::whereHas('project', fn ($q) => $q->where('workspace_id', $workspaceId))
                ->where('task_status_id', $inProgressId)
                ->where('created_at', '<', $lastWeek)
                ->count()
            : 0;

        // Overdue tasks (deadline passed, not done)
        $overdue = Task::whereHas('project', fn ($q) => $q->where('workspace_id', $workspaceId))
            ->whereNotIn('task_status_id', $doneStatusIds)
            ->whereNotNull('deadline')
            ->where('deadline', '<', $now->toDateString())
            ->count();
        $overduePrev = Task::whereHas('project', fn ($q) => $q->where('workspace_id', $workspaceId))
            ->whereNotIn('task_status_id', $doneStatusIds)
            ->whereNotNull('deadline')
            ->where('deadline', '<', $lastWeek->toDateString())
            ->count();

        // Completed this week
        $completedThisWeek = Task::whereHas('project', fn ($q) => $q->where('workspace_id', $workspaceId))
            ->whereIn('task_status_id', $doneStatusIds)
            ->where('updated_at', '>=', $lastWeek)
            ->count();
        $completedLastWeek = Task::whereHas('project', fn ($q) => $q->where('workspace_id', $workspaceId))
            ->whereIn('task_status_id', $doneStatusIds)
            ->whereBetween('updated_at', [$twoWeeks, $lastWeek])
            ->count();

        return [
            'activeProjects'         => $activeProjects,
            'activeProjectsDiff'     => $activeProjects - $activeProjectsPrev,
            'tasksInProgress'        => $tasksInProgress,
            'tasksInProgressDiff'    => $tasksInProgress - $tasksInProgressPrev,
            'overdueTasks'           => $overdue,
            'overdueTasksDiff'       => $overdue - $overduePrev,
            'completedThisWeek'      => $completedThisWeek,
            'completedThisWeekDiff'  => $completedThisWeek - $completedLastWeek,
        ];
    }

    private function getRecentProjects(int $workspaceId): array
    {
        return Project::where('workspace_id', $workspaceId)
            ->whereIn('status', ['active', 'planning', 'maintenance'])
            ->with([
                'projectMembers' => fn ($q) => $q->where('role', 'project_manager')->with('user'),
            ])
            ->withCount('tasks')
            ->orderByDesc('updated_at')
            ->limit(6)
            ->get()
            ->map(function (Project $project) {
                $manager = $project->projectMembers->first()?->user;
                return [
                    'id'        => $project->id,
                    'name'      => $project->name,
                    'color'     => $project->color,
                    'status'    => $project->status,
                    'progress'  => $project->progress,
                    'deadline'  => $project->deadline?->format('M d, Y'),
                    'manager'   => $manager ? [
                        'name'     => $manager->name,
                        'initials' => $this->initials($manager->name),
                    ] : null,
                    'taskCount' => $project->tasks_count,
                ];
            })
            ->toArray();
    }

    private function getMyTasks(int $userId, $doneStatusIds): array
    {
        return Task::where('assigned_to', $userId)
            ->whereNotIn('task_status_id', $doneStatusIds)
            ->with(['status', 'project'])
            ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->orderBy('deadline')
            ->limit(8)
            ->get()
            ->map(fn (Task $task) => [
                'id'          => $task->id,
                'title'       => $task->title,
                'priority'    => $task->priority,
                'statusName'  => $task->status->name,
                'statusColor' => $task->status->color,
                'projectName' => $task->project->name,
                'deadline'    => $task->deadline?->format('M d, Y'),
            ])
            ->toArray();
    }

    private function getRecentActivity(int $workspaceId): array
    {
        return ActivityLog::where('workspace_id', $workspaceId)
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn (ActivityLog $log) => [
                'id'          => $log->id,
                'description' => $log->description,
                'action'      => $log->action,
                'createdAt'   => $log->created_at->diffForHumans(),
                'user'        => [
                    'name'     => $log->user->name,
                    'initials' => $this->initials($log->user->name),
                ],
            ])
            ->toArray();
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
