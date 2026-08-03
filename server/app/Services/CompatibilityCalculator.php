<?php

namespace App\Services;

use App\Models\EventRegistration;
use App\Models\User;
use App\Models\UserBlock;

/**
 * A transparent, rule-based compatibility algorithm (spec section 17) — no
 * ML/AI. Every comparison is a plain, explainable function so match reasons
 * can be derived directly from the same signals used to score.
 */
class CompatibilityCalculator
{
    /**
     * Preferred group size on a registration is a raw headcount (not the
     * quiz's 1-5 scale), so "compatible" is judged against this tolerance
     * rather than an exact match. No threshold was specified, so this is a
     * reasonable "close enough" cutoff.
     */
    private const GROUP_SIZE_TOLERANCE = 3;

    /** Quiz answers sit on a 1-5 scale, so the largest possible gap is 4. */
    private const QUIZ_SCALE_MAX_DIFF = 4;

    public static function isSuitable(User $userA, EventRegistration $regA, User $userB, EventRegistration $regB): bool
    {
        if ($regA->event_id !== $regB->event_id) {
            return false;
        }

        if (! $regA->open_to_matching || ! $regB->open_to_matching) {
            return false;
        }

        if (self::isBlocked($userA->id, $userB->id)) {
            return false;
        }

        if (! self::interactionModesCompatible($regA->interaction_mode, $regB->interaction_mode)) {
            return false;
        }

        if (! self::groupSizesCompatible($regA->preferred_group_size, $regB->preferred_group_size)) {
            return false;
        }

        return true;
    }

    public static function calculateCompatibility(User $userA, EventRegistration $regA, User $userB, EventRegistration $regB): int
    {
        $score = 0;
        $score += self::compareCommunicationStyle($userA, $userB) * 0.35;
        $score += self::compareInteractionModes($userA, $userB) * 0.25;
        $score += self::compareNetworkingGoals($userA, $userB) * 0.20;
        $score += self::compareIndustries($userA, $userB) * 0.10;
        $score += self::compareEventPreferences($regA, $regB) * 0.10;

        return (int) round($score);
    }

    /**
     * Mirrors the same dimensions the score is built from, so every reason
     * shown to a user traces back to something the formula actually used.
     */
    public static function matchReasons(User $userA, EventRegistration $regA, User $userB, EventRegistration $regB): array
    {
        $reasons = [];

        if ($regA->interaction_mode !== 'either' && $regA->interaction_mode === $regB->interaction_mode) {
            $reasons[] = $regA->interaction_mode === 'one_to_one'
                ? 'Prefer one-to-one conversations'
                : 'Prefer small group conversations';
        }

        if ($regA->message_before_event && $regB->message_before_event) {
            $reasons[] = 'Like messaging before events';
        }

        $industryA = trim((string) $userA->industry);
        $industryB = trim((string) $userB->industry);
        if ($industryA !== '' && strcasecmp($industryA, $industryB) === 0) {
            $reasons[] = "Work in {$industryA}";
        }

        if (self::quizValue($userA, 'structuredConversation') >= 4 && self::quizValue($userB, 'structuredConversation') >= 4) {
            $reasons[] = 'Prefer structured conversations';
        }

        if (self::quizValue($userA, 'networkingGoal') === self::quizValue($userB, 'networkingGoal')) {
            $reasons[] = 'Share the same networking goal';
        }

        if ($regA->attendance_format !== null && $regA->attendance_format === $regB->attendance_format) {
            $reasons[] = $regA->attendance_format === 'virtual'
                ? 'Both attending virtually'
                : 'Both attending in person';
        }

        if (self::quizValue($userA, 'observeFirstPreference') >= 4 && self::quizValue($userB, 'observeFirstPreference') >= 4) {
            $reasons[] = 'Both prefer to observe before joining in';
        }

        if ($regA->preferred_group_size !== null
            && $regB->preferred_group_size !== null
            && abs($regA->preferred_group_size - $regB->preferred_group_size) <= 1) {
            $reasons[] = 'Want a similar group size';
        }

        return $reasons;
    }

    private static function compareCommunicationStyle(User $userA, User $userB): float
    {
        return self::averageQuizSimilarity($userA, $userB, ['messageBeforeMeeting', 'structuredConversation', 'responseSpeed']);
    }

    private static function compareInteractionModes(User $userA, User $userB): float
    {
        return self::averageQuizSimilarity($userA, $userB, ['oneToOnePreference']);
    }

    private static function compareNetworkingGoals(User $userA, User $userB): float
    {
        return self::quizValue($userA, 'networkingGoal') === self::quizValue($userB, 'networkingGoal') ? 100.0 : 0.0;
    }

    private static function compareIndustries(User $userA, User $userB): float
    {
        $industryA = trim((string) $userA->industry);
        $industryB = trim((string) $userB->industry);

        if ($industryA === '' || $industryB === '') {
            return 0.0;
        }

        return strcasecmp($industryA, $industryB) === 0 ? 100.0 : 0.0;
    }

    private static function compareEventPreferences(EventRegistration $regA, EventRegistration $regB): float
    {
        $formatScore = $regA->attendance_format === $regB->attendance_format ? 100.0 : 0.0;
        $messageScore = $regA->message_before_event === $regB->message_before_event ? 100.0 : 0.0;
        $groupSizeScore = max(0.0, 100.0 - (abs(($regA->preferred_group_size ?? 0) - ($regB->preferred_group_size ?? 0)) * 10));

        return ($formatScore + $messageScore + $groupSizeScore) / 3;
    }

    private static function averageQuizSimilarity(User $userA, User $userB, array $dimensions): float
    {
        $total = 0.0;
        foreach ($dimensions as $dimension) {
            $a = self::quizValue($userA, $dimension);
            $b = self::quizValue($userB, $dimension);
            $total += 100.0 * (1 - abs($a - $b) / self::QUIZ_SCALE_MAX_DIFF);
        }

        return $total / count($dimensions);
    }

    /**
     * Falls back to the neutral midpoint (3) when a user hasn't taken the
     * compatibility quiz yet, so matching still works — just less precisely
     * — before quiz completion rather than crashing or treating them as
     * automatically unsuitable (the quiz isn't one of the 5 spec'd gates).
     */
    private static function quizValue(User $user, string $key): int
    {
        return $user->quiz_answers[$key] ?? 3;
    }

    private static function interactionModesCompatible(?string $modeA, ?string $modeB): bool
    {
        if ($modeA === 'either' || $modeB === 'either') {
            return true;
        }

        return $modeA === $modeB;
    }

    private static function groupSizesCompatible(?int $sizeA, ?int $sizeB): bool
    {
        if ($sizeA === null || $sizeB === null) {
            return true;
        }

        return abs($sizeA - $sizeB) <= self::GROUP_SIZE_TOLERANCE;
    }

    private static function isBlocked(int $userIdA, int $userIdB): bool
    {
        return UserBlock::existsBetween($userIdA, $userIdB);
    }
}
