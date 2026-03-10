<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'email_notifications',
        'task_assignment_alerts',
        'deadline_reminders',
        'project_updates',
    ];

    protected $casts = [
        'email_notifications'    => 'boolean',
        'task_assignment_alerts' => 'boolean',
        'deadline_reminders'     => 'boolean',
        'project_updates'        => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
