<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventRegistration;
use App\Services\CompatibilityCalculator;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $myRegistrations = EventRegistration::query()
            ->where('user_id', $user->id)
            ->where('open_to_matching', true)
            ->with('event')
            ->get();

        $matches = [];

        foreach ($myRegistrations as $myRegistration) {
            $candidateRegistrations = EventRegistration::query()
                ->where('event_id', $myRegistration->event_id)
                ->where('user_id', '!=', $user->id)
                ->where('open_to_matching', true)
                ->with('user')
                ->get();

            foreach ($candidateRegistrations as $candidateRegistration) {
                $candidate = $candidateRegistration->user;

                if (! CompatibilityCalculator::isSuitable($user, $myRegistration, $candidate, $candidateRegistration)) {
                    continue;
                }

                $matches[] = [
                    'event' => [
                        'id' => $myRegistration->event->id,
                        'name' => $myRegistration->event->name,
                        'starts_at' => $myRegistration->event->starts_at,
                    ],
                    'user' => [
                        'id' => $candidate->id,
                        'first_name' => $candidate->first_name,
                        'last_name' => $candidate->last_name,
                        'job_title' => $candidate->job_title,
                        'industry' => $candidate->industry,
                        'profile_image' => $candidate->profile_image,
                        'availability_status' => $candidate->availability_status,
                        'availability_status_updated_at' => $candidate->availability_status_updated_at,
                    ],
                    'score' => CompatibilityCalculator::calculateCompatibility($user, $myRegistration, $candidate, $candidateRegistration),
                    'reasons' => CompatibilityCalculator::matchReasons($user, $myRegistration, $candidate, $candidateRegistration),
                ];
            }
        }

        usort($matches, fn ($a, $b) => $b['score'] <=> $a['score']);

        return response()->json(['data' => array_values($matches)]);
    }
}
