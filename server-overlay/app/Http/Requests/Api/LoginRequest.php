<?php

namespace App\Http\Requests\Api;

use App\Exceptions\ApiException;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticatedUser(): User
    {
        $user = User::where('email', $this->validated('email'))->first();

        if (! $user || ! password_verify($this->validated('password'), $user->password)) {
            throw new ApiException('These credentials do not match our records.', 'INVALID_CREDENTIALS', 401);
        }

        return $user;
    }
}
