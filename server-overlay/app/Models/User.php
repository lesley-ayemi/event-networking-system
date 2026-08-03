<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'bio',
        'networking_goals',
        'job_title',
        'industry',
        'profile_image',
        'interaction_preferences',
        'comfort_settings',
        'quiz_answers',
        'compatibility_profile',
        'onboarding_completed',
        'availability_status',
        'conversation_boundaries',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'bio' => '',
        'networking_goals' => '',
        'job_title' => '',
        'industry' => '',
        'profile_image' => '',
        'onboarding_completed' => false,
        'availability_status' => 'available',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'interaction_preferences' => 'array',
            'comfort_settings' => 'array',
            'quiz_answers' => 'array',
            'compatibility_profile' => 'array',
            'onboarding_completed' => 'boolean',
            'conversation_boundaries' => 'array',
        ];
    }

    /**
     * The default shape for interaction_preferences and comfort_settings mirrors
     * spec section 12's "Communication preferences" and "Comfort settings" field
     * lists exactly, so the frontend can rely on every key always being present.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (is_null($user->interaction_preferences)) {
                $user->interaction_preferences = [
                    'one_to_one' => true,
                    'small_groups' => false,
                    'virtual_interaction' => false,
                    'text_communication' => true,
                    'meet_before_event' => false,
                    'observe_first' => false,
                ];
            }

            if (is_null($user->comfort_settings)) {
                $user->comfort_settings = [
                    'max_group_size' => 4,
                    'allow_message_first' => true,
                    'auto_matching' => true,
                    'pre_event_introductions' => true,
                    'event_reminders' => true,
                ];
            }

            if (is_null($user->quiz_answers)) {
                $user->quiz_answers = [];
            }

            if (is_null($user->compatibility_profile)) {
                $user->compatibility_profile = [];
            }

            if (is_null($user->conversation_boundaries)) {
                $user->conversation_boundaries = [
                    'text_only' => false,
                    'no_video_calls' => false,
                    'one_message_at_a_time' => false,
                    'event_only_meeting' => false,
                    'no_spontaneous_calls' => false,
                    'ask_before_groups' => false,
                ];
            }
        });
    }

    public function registeredEvents(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_registrations')->withTimestamps();
    }

    public function bookmarkedEvents(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'bookmarks')->withTimestamps();
    }

    public function blockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_blocks', 'blocker_id', 'blocked_id')->withTimestamps();
    }
}
