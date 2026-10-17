<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class AuditService
{
    // Record an administrative action in the shared audit log.
    public function record(
        string $action,
        ?string $description = null,
        array $old = [],
        array $new = [],
        ?Model $auditable = null,
        ?User $actor = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => ($actor ?? auth()->user())?->id,
            'action' => $action,
            'description' => $description,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $old !== [] ? $old : null,
            'new_values' => $new !== [] ? $new : null,
            'ip_address' => Request::ip(),
        ]);
    }
}
