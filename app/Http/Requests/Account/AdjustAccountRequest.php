<?php

namespace App\Http\Requests\Account;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class AdjustAccountRequest extends FormRequest
{
    /**
     * Only admins may adjust a member's points balance.
     */
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        return $user !== null && $user->hasRole(Role::Admin);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'points' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
