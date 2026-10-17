<?php

namespace App\Http\Requests\CampaignPrize;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignPrizeRequest extends FormRequest
{
    /**
     * Only admins may manage the spin wheel.
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
            'label' => ['required', 'string', 'max:255'],
            'weight' => ['required', 'integer', 'min:1', 'max:1000000'],
            'points' => ['required', 'integer', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
