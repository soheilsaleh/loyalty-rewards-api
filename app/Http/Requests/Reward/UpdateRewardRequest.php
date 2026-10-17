<?php

namespace App\Http\Requests\Reward;

use App\Enums\Role;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRewardRequest extends FormRequest
{
    /**
     * Only admins may manage the reward catalog.
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
        $reward = $this->route('reward');
        $id = $reward instanceof Reward ? $reward->getKey() : null;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', 'alpha_dash', Rule::unique('rewards', 'slug')->ignore($id)],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'cost_points' => ['sometimes', 'required', 'integer', 'min:1'],
            'stock' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
