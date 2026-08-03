<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistration extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'interaction_mode',
        'open_to_matching',
        'message_before_event',
        'preferred_group_size',
        'attendance_format',
    ];

    protected function casts(): array
    {
        return [
            'open_to_matching' => 'boolean',
            'message_before_event' => 'boolean',
            'preferred_group_size' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
