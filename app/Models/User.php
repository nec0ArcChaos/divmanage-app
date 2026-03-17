<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'avatar',
        'department',
        'phone',
        'role_id',
        'status_id',
        'job_id',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
        'role',
        'memberStatus',
        'jobTitle',
    ];

    /**
     * Virtual attributes appended to JSON serialisation.
     * These expose relationship-derived values under the original field names
     * so frontend code (global_role, status, job_title) needs no changes.
     */
    protected $appends = ['global_role', 'status', 'job_title'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    // ── Relationships ───────────────────────────────────────────────────────

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function memberStatus(): BelongsTo
    {
        return $this->belongsTo(MemberStatus::class, 'status_id');
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class, 'job_id');
    }

    public function projectMembers(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    // ── Accessors (backward-compatible virtual attributes) ──────────────────

    public function getGlobalRoleAttribute(): ?string
    {
        return $this->role?->slug;
    }

    public function getStatusAttribute(): ?string
    {
        return $this->memberStatus?->slug;
    }

    public function getJobTitleAttribute(): ?string
    {
        return $this->getRelationValue('jobTitle')?->name;
    }
}
