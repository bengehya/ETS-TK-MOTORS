<?php

namespace App\Exceptions;

use RuntimeException;

class MissingPurchasePriceException extends RuntimeException
{
    public ?int $productId = null;
}
