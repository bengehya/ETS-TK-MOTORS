<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class UserFacingErrorResponse
{
    public const FAILURE = 'L’opération n’a pas abouti. Réessayez.';

    public const DENIED = 'Action non autorisée.';

    public function replace(Response $response, Throwable $exception, Request $request): Response
    {
        $status = $response->getStatusCode();

        if ($status !== 403 && $status < 500) {
            return $response;
        }

        $message = $status === 403 ? self::DENIED : self::FAILURE;

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        if ($status >= 500 && $request->headers->has('X-Inertia') && ! $request->isMethod('GET')) {
            try {
                return redirect()->back()->with('error', $message);
            } catch (Throwable) {
                return response()->view('errors.operation', ['message' => $message], $status);
            }
        }

        if ($request->headers->has('X-Inertia')) {
            return Inertia::render('Errors/Operation', ['message' => $message])
                ->toResponse($request)
                ->setStatusCode($status);
        }

        return response()->view('errors.operation', ['message' => $message], $status);
    }
}
