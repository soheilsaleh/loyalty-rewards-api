<?php

namespace App\Http\Requests\Campaign;

use App\Enums\Role;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaignRequest extends FormRequest
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
        $campaign = $this->route('campaign');
        $id = $campaign instanceof Campaign ? $campaign->getKey() : null;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', 'alpha_dash', Rule::unique('campaigns', 'slug')->ignore($id)],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'multiplier' => ['sometimes', 'numeric', 'min:1', 'max:10'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => array_filter([
                'sometimes', 'nullable', 'date',
                $this->filled('starts_at') ? 'after_or_equal:starts_at' : null,
            ]),
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
