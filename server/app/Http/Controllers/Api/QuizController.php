<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateQuizRequest;

class QuizController extends Controller
{
    public function update(UpdateQuizRequest $request)
    {
        $user = $request->user();
        $user->quiz_answers = $request->validated();
        $user->save();

        return response()->json($user);
    }
}
