<?php

namespace App\Http\Requests\Campaign;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    /**
     * Only admins may manage campaigns.
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:campaigns,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'multiplier' => ['sometimes', 'numeric', 'min:1', 'max:10'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => array_filter([
                'nullable', 'date',
                $this->filled('starts_at') ? 'after_or_equal:starts_at' : null,
            ]),
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
