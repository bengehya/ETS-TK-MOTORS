<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientPaymentException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $currency = null,
        public readonly ?string $total = null,
        public readonly ?string $received = null,
    ) {
        parent::__construct($message);
    }
}
