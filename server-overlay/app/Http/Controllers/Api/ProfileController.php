<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        // Nested preference/comfort updates are merged onto the existing shape
        // rather than replacing it outright, so saving one onboarding step
        // never wipes out fields collected on another.
        if (array_key_exists('interaction_preferences', $validated)) {
            $validated['interaction_preferences'] = array_merge(
                $user->interaction_preferences ?? [],
                $validated['interaction_preferences']
            );
        }

        if (array_key_exists('comfort_settings', $validated)) {
            $validated['comfort_settings'] = array_merge(
                $user->comfort_settings ?? [],
                $validated['comfort_settings']
            );
        }

        if (array_key_exists('conversation_boundaries', $validated)) {
            $validated['conversation_boundaries'] = array_merge(
                $user->conversation_boundaries ?? [],
                $validated['conversation_boundaries']
            );
        }

        $user->fill($validated);
        $user->save();

        return response()->json($user);
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:4096'],
        ]);

        $user = $request->user();

        if ($user->profile_image) {
            $previousPath = Str::after($user->profile_image, '/storage/');
            if ($previousPath && Storage::disk('public')->exists($previousPath)) {
                Storage::disk('public')->delete($previousPath);
            }
        }

        $path = $request->file('photo')->store('profile-photos', 'public');
        $user->profile_image = Storage::disk('public')->url($path);
        $user->save();

        return response()->json($user);
    }
}
