<?php

namespace App\Http\Controllers;

use App\Models\ProjectMember;
use App\Models\TaskCommentAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskAttachmentController extends Controller
{
    /**
     * Stream the attachment file to the browser (project members only).
     */
    public function download(TaskCommentAttachment $attachment): StreamedResponse
    {
        $task = $attachment->comment->task;

        $isMember = ProjectMember::where('project_id', $task->project_id)
            ->where('user_id', Auth::id())
            ->exists();

        if (! $isMember) {
            abort(403);
        }

        if (! Storage::disk('local')->exists($attachment->path)) {
            abort(404);
        }

        return Storage::disk('local')->download(
            $attachment->path,
            $attachment->original_name
        );
    }

    /**
     * Delete an attachment (uploader only).
     */
    public function destroy(TaskCommentAttachment $attachment): JsonResponse
    {
        if ($attachment->user_id !== Auth::id()) {
            abort(403);
        }

        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();

        return response()->json(['deleted' => true]);
    }
}
