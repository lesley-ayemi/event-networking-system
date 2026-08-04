<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    public const REASONS = [
        'harassment',
        'spam',
        'inappropriate_messages',
        'false_event_information',
        'impersonation',
        'unsafe_behaviour',
    ];

    public const STATUSES = ['pending', 'reviewed', 'dismissed', 'actioned'];

    // Short, stable aliases a client may submit for reportable_type — resolved
    // to model classes via the morph map registered in AppServiceProvider,
    // rather than trusting a raw class name from the request.
    public const REPORTABLE_TYPES = ['user', 'message', 'event'];

    protected $fillable = [
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'details',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    protected $attributes = [
        'status' => 'pending',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }
}
