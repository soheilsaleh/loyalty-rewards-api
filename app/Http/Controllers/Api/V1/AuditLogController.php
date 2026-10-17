<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuditLogController extends Controller
{
    /**
     * The audit trail, newest first, filterable by action or actor (admin only).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')->toString()))
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->latest('id')
            ->cursorPaginate(25);

        return AuditLogResource::collection($logs);
    }
}
