<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function index()
    {
        $user        = Auth::user();
        $workspaceId = $user->workspace_id;

        if ($workspaceId === null) {
            return Inertia::render('Team', [
                'members'        => [],
                'stats'          => $this->emptyStats(),
                'activeProjects' => [],
            ]);
        }

        $members = User::where('workspace_id', $workspaceId)
            ->with(['projectMembers' => fn ($q) => $q->with('project')])
            ->orderByRaw("FIELD(global_role, 'admin', 'project_manager', 'developer', 'qa')")
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id'          => $u->id,
                'name'        => $u->name,
                'username'    => $u->username,
                'email'       => $u->email,
                'avatar'      => $u->avatar,
                'global_role' => $u->global_role,
                'status'      => $u->status,
                'job_title'   => $u->job_title,
                'initials'    => $this->initials($u->name),
                'projects'    => $u->projectMembers->map(fn ($pm) => [
                    'id'    => $pm->project_id,
                    'name'  => $pm->project->name,
                    'color' => $pm->project->color,
                    'role'  => $pm->role,
                ])->values(),
            ]);

        $stats = [
            'total'           => $members->count(),
            'developers'      => $members->where('global_role', 'developer')->count(),
            'qa'              => $members->where('global_role', 'qa')->count(),
            'projectManagers' => $members->whereIn('global_role', ['admin', 'project_manager'])->count(),
        ];

        $activeProjects = Project::where('workspace_id', $workspaceId)
            ->whereIn('status', ['active', 'planning'])
            ->with('projectMembers.user')
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project) => [
                'id'      => $project->id,
                'name'    => $project->name,
                'color'   => $project->color,
                'members' => $project->projectMembers->map(fn ($pm) => [
                    'name'     => $pm->user->name,
                    'initials' => $this->initials($pm->user->name),
                ])->values(),
            ]);

        return Inertia::render('Team', [
            'members'        => $members,
            'stats'          => $stats,
            'activeProjects' => $activeProjects,
        ]);
    }

    private function emptyStats(): array
    {
        return ['total' => 0, 'developers' => 0, 'qa' => 0, 'projectManagers' => 0];
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
