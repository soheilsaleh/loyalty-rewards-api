<?php

namespace App\Http\Resources;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AuditLog
 */
class AuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $action = AuditAction::tryFrom($this->action);

        return [
            'id' => $this->id,
            'action' => $this->action,
            'action_label' => $action?->label(),
            'auditable' => $this->auditable_type === null ? null : [
                'type' => class_basename((string) $this->auditable_type),
                'id' => $this->auditable_id,
            ],
            'changes' => $this->changes,
            'ip_address' => $this->ip_address,
            'actor' => $this->whenLoaded('user', fn () => $this->user === null ? null : [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
