<?php

namespace App\Exceptions;

use Illuminate\Foundation\Configuration\Exceptions;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class OperationExceptionHandler
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->dontReportDuplicates();
        $exceptions->dontReport([
            InsufficientPaymentException::class,
            InsufficientCashException::class,
            MissingExchangeRateException::class,
            DuplicateSaleException::class,
            InsufficientStockException::class,
            InactiveProductException::class,
            MissingPurchasePriceException::class,
            SaleAlreadyCancelledException::class,
            OperationAlreadyProcessedException::class,
            ArrivalAlreadyProcessedException::class,
            UnsellableLocationException::class,
        ]);

        $exceptions->reportable(function (Throwable $exception): void {
            if ($exception instanceof HttpExceptionInterface) {
                return;
            }

            app(UnexpectedErrorAuditor::class)->record($exception);
        });

        $exceptions->respond(function ($response, Throwable $exception, $request) {
            return app(UserFacingErrorResponse::class)->replace($response, $exception, $request);
        });
    }
}
