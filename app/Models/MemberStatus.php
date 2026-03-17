<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberStatus extends Model
{
    protected $table = 'member_statuses';

    protected $fillable = ['slug', 'name'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'status_id');
    }
}
