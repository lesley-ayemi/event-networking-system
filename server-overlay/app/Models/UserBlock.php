<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBlock extends Model
{
    protected $fillable = [
        'blocker_id',
        'blocked_id',
    ];

    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocker_id');
    }

    public function blocked(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_id');
    }

    public static function existsBetween(int $userIdA, int $userIdB): bool
    {
        return self::query()
            ->where(function ($query) use ($userIdA, $userIdB) {
                $query->where('blocker_id', $userIdA)->where('blocked_id', $userIdB);
            })
            ->orWhere(function ($query) use ($userIdA, $userIdB) {
                $query->where('blocker_id', $userIdB)->where('blocked_id', $userIdA);
            })
            ->exists();
    }
}
