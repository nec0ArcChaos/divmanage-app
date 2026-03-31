<?php

namespace App\Http\Controllers;

use App\Events\NewNotificationCreated;
use App\Events\TaskCommentCreated;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskCommentAttachment;
use App\Models\User;
use App\Notifications\TaskCommentNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaskCommentController extends Controller
{
    /**
     * Return all comments (with attachments & user) for a task.
     */
    public function index(Task $task): JsonResponse
    {
        $this->authorizeProjectMember($task);

        $comments = $task->comments()
            ->with(['user', 'attachments'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (TaskComment $c) => $this->formatComment($c));

        return response()->json($comments);
    }

    /**
     * Store a new comment (with optional file attachments).
     */
    public function store(Request $request, Task $task): JsonResponse
    {
        $this->authorizeProjectMember($task);

        $request->validate([
            'body'          => ['required', 'string', 'max:65535'],
            'attachments'   => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'], // 10 MB each
        ]);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'body'    => $request->input('body'),
        ]);

        // Handle file uploads
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path       = $file->storeAs("attachments/{$task->id}", $storedName, 'local');

                TaskCommentAttachment::create([
                    'task_comment_id' => $comment->id,
                    'user_id'         => Auth::id(),
                    'original_name'   => $file->getClientOriginalName(),
                    'stored_name'     => $storedName,
                    'path'            => $path,
                    'mime_type'       => $file->getMimeType(),
                    'size'            => $file->getSize(),
                ]);
            }
        }

        $comment->load(['user', 'attachments']);

        $formatted = $this->formatComment($comment);

        broadcast(new TaskCommentCreated($formatted, $task->id))->toOthers();

        // Send notifications
        $this->sendNotifications($task, $comment);

        return response()->json($formatted, 201);
    }

    /**
     * Delete a comment (author only).
     */
    public function destroy(TaskComment $comment): JsonResponse
    {
        if ($comment->user_id !== Auth::id()) {
            abort(403);
        }

        // Delete attachment files from disk
        foreach ($comment->attachments as $attachment) {
            Storage::disk('local')->delete($attachment->path);
        }

        $comment->delete();

        return response()->json(['deleted' => true]);
    }

    // ── Private helpers ────────────────────────────────────────────────────

    private function authorizeProjectMember(Task $task): void
    {
        $isMember = ProjectMember::where('project_id', $task->project_id)
            ->where('user_id', Auth::id())
            ->exists();

        if (! $isMember) {
            abort(403);
        }
    }

    private function formatComment(TaskComment $comment): array
    {
        return [
            'id'          => $comment->id,
            'body'        => $comment->body,
            'created_at'  => $comment->created_at->diffForHumans(),
            'created_at_iso' => $comment->created_at->toISOString(),
            'is_mine'     => $comment->user_id === Auth::id(),
            'user'        => [
                'id'       => $comment->user->id,
                'name'     => $comment->user->name,
                'initials' => $this->initials($comment->user->name),
            ],
            'attachments' => $comment->attachments->map(fn (TaskCommentAttachment $a) => [
                'id'            => $a->id,
                'original_name' => $a->original_name,
                'mime_type'     => $a->mime_type,
                'size'          => $a->size,
                'size_human'    => $this->humanSize($a->size),
                'download_url'  => route('task-attachments.download', $a->id),
                'is_image'      => str_starts_with($a->mime_type ?? '', 'image/'),
            ])->values()->toArray(),
        ];
    }

    private function sendNotifications(Task $task, TaskComment $comment): void
    {
        $task->load(['assignee', 'project.projectMembers.user']);

        $commenterId  = Auth::id();
        $notifyUsers  = collect();

        // Notify task assignee
        if ($task->assignee && $task->assignee->id !== $commenterId) {
            $notifyUsers->push($task->assignee);
        }

        // Notify project manager(s)
        if ($task->project) {
            $managers = $task->project->projectMembers
                ->filter(fn ($pm) => $pm->role === 'project_manager' && $pm->user_id !== $commenterId)
                ->map(fn ($pm) => $pm->user);

            foreach ($managers as $manager) {
                if (! $notifyUsers->contains('id', $manager->id)) {
                    $notifyUsers->push($manager);
                }
            }
        }

        $commenter = Auth::user();

        foreach ($notifyUsers as $user) {
            // notifyNow() stores synchronously so we can immediately get the DB record
            $user->notifyNow(new TaskCommentNotification($comment, $task, $commenter));

            $stored = $user->notifications()->latest()->first();
            if ($stored) {
                broadcast(new NewNotificationCreated($user->id, [
                    'id'         => $stored->id,
                    'data'       => $stored->data,
                    'read_at'    => null,
                    'created_at' => $stored->created_at->diffForHumans(),
                ]));
            }
        }
    }

    private function initials(string $name): string
    {
        $parts = explode(' ', trim($name));
        if (count($parts) >= 2) {
            return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
        }

        return strtoupper(mb_substr($name, 0, 2));
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
