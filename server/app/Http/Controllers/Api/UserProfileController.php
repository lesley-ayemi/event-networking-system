<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    /**
     * A curated, public-safe view of another user's profile — deliberately
     * not the raw model, which still carries email, comfort_settings,
     * quiz_answers, and other fields only appropriate for viewing your own
     * account via /api/user. Blocked (either direction) and suspended
     * accounts 404 rather than expose why, matching how blocked users are
     * simply omitted elsewhere (matches, friends) rather than shown with an
     * explanatory error.
     */
    public function show(Request $request, User $user)
    {
        $viewer = $request->user();

        if ($user->is_suspended || UserBlock::existsBetween($viewer->id, $user->id)) {
            throw new ApiException("We couldn't find that profile.", 'USER_NOT_FOUND', 404);
        }

        return response()->json([
            'data' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'bio' => $user->bio,
                'job_title' => $user->job_title,
                'industry' => $user->industry,
                'networking_goals' => $user->networking_goals,
                'profile_image' => $user->profile_image,
                'availability_status' => $user->availability_status,
            ],
        ]);
    }
}
