<?php

namespace App\Exceptions;

use App\Services\AuditLogger;
use Illuminate\Support\Str;
use Throwable;

class UnexpectedErrorAuditor
{
    /** @var array<int, true> */
    private static array $recorded = [];

    public function __construct(private readonly AuditLogger $audit) {}

    public function record(Throwable $exception): void
    {
        $identity = spl_object_id($exception);

        if (isset(self::$recorded[$identity])) {
            return;
        }

        self::$recorded[$identity] = true;

        $request = request();
        $message = Str::limit(preg_replace('/\s+/', ' ', $exception->getMessage()) ?? '', 300);

        $this->audit->safeRecord(
            $request?->user(),
            'system.error',
            null,
            null,
            [
                'exception' => class_basename($exception),
                'message' => $message,
                'route' => $request?->route()?->getName(),
                'method' => $request?->method(),
            ],
            'Erreur technique inattendue.',
            'failure',
        );
    }
}
