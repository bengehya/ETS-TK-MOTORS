<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

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
        string $result = 'success',
        ?string $correlationId = null,
    ): AuditLog {
        $organizationId = $user?->organization_id;

        if ($organizationId === null && $subject !== null && isset($subject->organization_id)) {
            $organizationId = $subject->organization_id;
        }

        if ($organizationId === null) {
            throw new InvalidArgumentException('Un journal d’audit exige une organisation.');
        }

        $reason = $reason !== null ? trim($reason) : null;
        $correlationId ??= $this->correlationId();

        return AuditLog::query()->create([
            'organization_id' => $organizationId,
            'user_id' => $user?->id,
            'actor_role' => $user?->role?->value,
            'action' => $action,
            'result' => $result,
            'subject_type' => $subject !== null ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'correlation_id' => $correlationId,
            'reason' => $reason === '' ? null : $reason,
            'old_values' => $this->scrub($oldValues),
            'new_values' => $this->scrub($newValues),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function safeRecord(
        ?User $user,
        string $action,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $reason = null,
        string $result = 'failure',
        ?string $correlationId = null,
    ): ?AuditLog {
        try {
            return $this->record($user, $action, $subject, $oldValues, $newValues, $reason, $result, $correlationId);
        } catch (Throwable $exception) {
            try {
                Log::warning('audit.record_failed', [
                    'action' => $action,
                    'message' => $exception->getMessage(),
                ]);
            } catch (Throwable) {
                // La journalisation ne doit jamais empêcher l'opération appelante.
            }

            return null;
        }
    }

    private function correlationId(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = request();
        $value = $request->attributes->get('correlation_id');

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function scrub(mixed $value, ?string $key = null): mixed
    {
        if ($key !== null && $this->isSensitive($key)) {
            return '[retiré]';
        }

        if (! is_array($value)) {
            return $value;
        }

        $clean = [];

        foreach ($value as $childKey => $child) {
            $name = is_string($childKey) ? $childKey : null;
            $clean[$childKey] = $this->scrub($child, $name);
        }

        return $clean;
    }

    private function isSensitive(string $key): bool
    {
        return in_array(strtolower($key), [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'remember_token',
            'api_token',
            'secret',
            'cookie',
            'cookies',
            'authorization',
            'invitation_code',
            'plain_code',
        ], true);
    }
}
