<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskCommentAttachment extends Model
{
    protected $fillable = [
        'task_comment_id',
        'user_id',
        'original_name',
        'stored_name',
        'path',
        'mime_type',
        'size',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(TaskComment::class, 'task_comment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
