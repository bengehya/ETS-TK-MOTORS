<?php

namespace App\Exceptions;

use RuntimeException;

class InactiveProductException extends RuntimeException
{
    public ?int $productId = null;
}
