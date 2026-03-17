<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Models\JobTitle;
use App\Models\MemberStatus;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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
                'roles'          => [],
                'statuses'       => [],
                'jobTitles'      => [],
            ]);
        }

        $members = User::where('workspace_id', $workspaceId)
            ->with(['projectMembers' => fn ($q) => $q->with('project'), 'role', 'memberStatus', 'jobTitle'])
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->orderBy('roles.sort_order')
            ->orderBy('users.name')
            ->select('users.*')
            ->get()
            ->map(fn (User $u) => [
                'id'          => $u->id,
                'name'        => $u->name,
                'username'    => $u->username,
                'email'       => $u->email,
                'phone'       => $u->phone,
                'avatar'      => $u->avatar,
                'global_role' => $u->global_role,
                'status'      => $u->status,
                'status_id'   => $u->status_id,
                'job_title'   => $u->job_title,
                'role_id'     => $u->role_id,
                'job_id'      => $u->job_id,
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
            'roles'          => Role::orderBy('sort_order')->get(['id', 'slug', 'name']),
            'statuses'       => MemberStatus::all(['id', 'slug', 'name']),
            'jobTitles'      => JobTitle::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $rawPhone = $request->phone ? trim($request->phone) : null;
        $phone    = $rawPhone ? '+62 ' . ltrim($rawPhone, '0') : null;

        User::create([
            'name'              => $request->name,
            'username'          => $request->username,
            'email'             => $request->email,
            'password'          => Hash::make('password'),
            'role_id'           => $request->role_id,
            'status_id'         => MemberStatus::where('slug', 'active')->value('id'),
            'job_id'            => $request->job_id,
            'department'        => 'IT Division',
            'phone'             => $phone,
            'workspace_id'      => Auth::user()->workspace_id,
            'email_verified_at' => now(),
        ]);

        return redirect()->route('team.index');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array(Auth::user()->global_role, ['admin', 'project_manager']), 403);

        $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'username'  => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'email'     => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role_id'   => ['required', 'integer', Rule::exists('roles', 'id')],
            'job_id'    => ['required', 'integer', Rule::exists('job_titles', 'id')],
            'status_id' => ['required', 'integer', Rule::exists('member_statuses', 'id')],
            'phone'     => ['nullable', 'string', 'max:30'],
        ]);

        $user->update([
            'name'      => $request->name,
            'username'  => $request->username,
            'email'     => $request->email,
            'role_id'   => $request->role_id,
            'job_id'    => $request->job_id,
            'status_id' => $request->status_id,
            'phone'     => $request->phone ?: null,
        ]);

        return back();
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless(Auth::user()->global_role === 'admin', 403);
        abort_if($user->id === Auth::id(), 403);

        $user->delete();

        return redirect()->route('team.index');
    }

    public function storeJobTitle(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->global_role === 'admin', 403);

        $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:job_titles,name'],
        ], [
            'name.required' => 'Nama bidang wajib diisi.',
            'name.unique'   => 'Bidang ini sudah ada.',
        ]);

        JobTitle::create(['name' => $request->name]);

        return back();
    }

    public function destroyJobTitle(JobTitle $jobTitle): RedirectResponse
    {
        abort_unless(Auth::user()->global_role === 'admin', 403);

        $jobTitle->delete();

        return back();
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
