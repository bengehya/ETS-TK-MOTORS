<?php

namespace App\Enums;

enum Currency: string
{
    case Usd = 'USD';
    case Cdf = 'CDF';

    public function label(): string
    {
        return match ($this) {
            self::Usd => 'USD',
            self::Cdf => 'CDF',
        };
    }

    public function opposite(): self
    {
        return $this === self::Usd ? self::Cdf : self::Usd;
    }
}
