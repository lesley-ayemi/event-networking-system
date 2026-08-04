<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrganiserRequestController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->organiser_status === 'approved') {
            throw new ApiException('You are already an approved event organiser.', 'ALREADY_APPROVED_ORGANISER', 409);
        }

        if ($user->organiser_status === 'pending') {
            throw new ApiException('Your organiser request is already pending review.', 'ORGANISER_REQUEST_PENDING', 409);
        }

        $user->organiser_status = 'pending';
        $user->organiser_requested_at = now();
        $user->save();

        return response()->json($user);
    }
}
