<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'job_title',
        'industry',
        'profile_image',
        'interaction_preferences',
        'quiz_answers',
        'compatibility_profile',
        'onboarding_completed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'bio' => '',
        'job_title' => '',
        'industry' => '',
        'profile_image' => '',
        'onboarding_completed' => false,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'interaction_preferences' => 'array',
            'quiz_answers' => 'array',
            'compatibility_profile' => 'array',
            'onboarding_completed' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (is_null($user->interaction_preferences)) {
                $user->interaction_preferences = [
                    'preferred_mode' => 'one-to-one',
                    'preferred_group_size' => 2,
                    'virtual_preferred' => false,
                    'message_before_event' => true,
                    'allow_match_requests' => true,
                ];
            }

            if (is_null($user->quiz_answers)) {
                $user->quiz_answers = [];
            }

            if (is_null($user->compatibility_profile)) {
                $user->compatibility_profile = [];
            }
        });
    }
}
