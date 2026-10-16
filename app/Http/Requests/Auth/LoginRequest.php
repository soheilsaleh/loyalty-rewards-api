<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Verify the credentials and return the matching user.
     *
     * Credentials are checked directly (rather than with the session guard) so
     * that API logins have no session side effects.
     *
     * @throws ValidationException
     */
    public function authenticate(): User
    {
        $user = User::query()->where('email', $this->input('email'))->first();

        if (! $user || ! Hash::check((string) $this->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        return $user;
    }
}
