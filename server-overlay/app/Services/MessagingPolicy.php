<?php

namespace App\Services;

use App\Models\FriendRequest;
use App\Models\User;

/**
 * Encodes spec section 18's messaging rule: two users may message only once
 * they're friends, unless both have independently opted into open messaging
 * (comfort_settings.allow_message_first). No messaging feature exists yet to
 * consume this — it's built now so the future Messages feature has a single,
 * tested source of truth to check against rather than reimplementing the
 * rule ad hoc.
 */
class MessagingPolicy
{
    public static function canMessage(User $userA, User $userB): bool
    {
        if ($userA->id === $userB->id) {
            return false;
        }

        if (self::areFriends($userA, $userB)) {
            return true;
        }

        return self::hasOpenMessaging($userA) && self::hasOpenMessaging($userB);
    }

    private static function areFriends(User $userA, User $userB): bool
    {
        return FriendRequest::query()
            ->where('status', 'accepted')
            ->where(function ($query) use ($userA, $userB) {
                $query->where(function ($q) use ($userA, $userB) {
                    $q->where('sender_id', $userA->id)->where('recipient_id', $userB->id);
                })->orWhere(function ($q) use ($userA, $userB) {
                    $q->where('sender_id', $userB->id)->where('recipient_id', $userA->id);
                });
            })
            ->exists();
    }

    private static function hasOpenMessaging(User $user): bool
    {
        return (bool) ($user->comfort_settings['allow_message_first'] ?? false);
    }
}
