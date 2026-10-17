<?php

namespace App\Http\Requests\Redeem;

use Illuminate\Foundation\Http\FormRequest;

class RedeemRequest extends FormRequest
{
    /**
     * Any authenticated member may redeem a reward.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Expose the Idempotency-Key header as a validated input.
     */
    protected function prepareForValidation(): void
    {
        $key = $this->header('Idempotency-Key');

        if (is_string($key) && $key !== '') {
            $this->merge(['idempotency_key' => $key]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:255'],
        ];
    }
}
