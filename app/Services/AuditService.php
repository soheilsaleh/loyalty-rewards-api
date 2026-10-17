<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    /**
     * Record a privileged action in the audit trail.
     *
     * @param  array<string, mixed>  $changes
     */
    public function record(
        ?User $actor,
        AuditAction $action,
        ?Model $auditable = null,
        array $changes = [],
    ): AuditLog {
        $log = new AuditLog([
            'action' => $action->value,
            'changes' => $changes === [] ? null : $changes,
            'ip_address' => request()->ip(),
        ]);

        if ($actor !== null) {
            $log->user()->associate($actor);
        }

        if ($auditable !== null) {
            $log->auditable()->associate($auditable);
        }

        $log->save();

        return $log;
    }
}
