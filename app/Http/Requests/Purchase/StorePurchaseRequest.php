<?php

namespace App\Http\Requests\Purchase;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    /**
     * Only staff and admins may register purchases.
     */
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        return $user !== null && $user->hasRole(Role::Staff, Role::Admin);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'occurred_at' => ['nullable', 'date'],
            'external_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
