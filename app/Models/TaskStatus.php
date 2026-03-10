<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskStatus extends Model
{
    protected $fillable = [
        'workspace_id',
        'name',
        'slug',
        'color',
        'position',
        'is_default',
        'is_done',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_done'    => 'boolean',
        'position'   => 'integer',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
