<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProjectMemberController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $this->authorizeWorkspace($project);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role'    => ['required', Rule::in(['project_manager', 'developer', 'qa', 'designer', 'viewer'])],
        ]);

        // Ensure user belongs to the same workspace
        $user = User::where('id', $validated['user_id'])
            ->where('workspace_id', $project->workspace_id)
            ->first();

        if (! $user) {
            return back()->withErrors(['user_id' => 'The selected user does not belong to this workspace.']);
        }

        // Prevent duplicate membership
        if ($project->projectMembers()->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['user_id' => 'This user is already a member of this project.']);
        }

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id'    => $user->id,
            'role'       => $validated['role'],
            'joined_at'  => now(),
        ]);

        return redirect()->route('projects.index');
    }

    public function update(Request $request, Project $project, User $user)
    {
        $this->authorizeWorkspace($project);

        $validated = $request->validate([
            'role' => ['required', Rule::in(['project_manager', 'developer', 'qa', 'designer', 'viewer'])],
        ]);

        $project->projectMembers()
            ->where('user_id', $user->id)
            ->firstOrFail()
            ->update(['role' => $validated['role']]);

        return redirect()->route('projects.index');
    }

    public function destroy(Project $project, User $user)
    {
        $this->authorizeWorkspace($project);

        $project->projectMembers()
            ->where('user_id', $user->id)
            ->firstOrFail()
            ->delete();

        return redirect()->route('projects.index');
    }

    private function authorizeWorkspace(Project $project): void
    {
        if ($project->workspace_id !== Auth::user()->workspace_id) {
            abort(403);
        }
    }
}
