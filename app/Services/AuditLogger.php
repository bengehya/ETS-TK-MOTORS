<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function record(
        ?User $user,
        string $action,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null,
    ): AuditLog {
        $organizationId = $user?->organization_id;

        if ($organizationId === null && $subject !== null && isset($subject->organization_id)) {
            $organizationId = $subject->organization_id;
        }

        if ($organizationId === null) {
            throw new InvalidArgumentException('Un journal d’audit exige une organisation.');
        }

        $reason = $reason !== null ? trim($reason) : null;

        return AuditLog::query()->create([
            'organization_id' => $organizationId,
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => $subject !== null ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'reason' => $reason === '' ? null : $reason,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
