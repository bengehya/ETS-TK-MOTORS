<?php

namespace App\Support;

final class Money
{
    public static function normalize(string|int|float|null $value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }

        return bcadd((string) $value, '0', 2);
    }

    public static function mul(string $unit, int $quantity): string
    {
        return bcmul(self::normalize($unit), (string) $quantity, 2);
    }

    public static function add(string $left, string $right): string
    {
        return bcadd(self::normalize($left), self::normalize($right), 2);
    }

    public static function sub(string $left, string $right): string
    {
        return bcsub(self::normalize($left), self::normalize($right), 2);
    }

    public static function div(string $amount, string $divisor): string
    {
        return bcdiv(self::normalize($amount), $divisor, 2);
    }

    public static function percent(string $amount, string $percent): string
    {
        return bcdiv(bcmul(self::normalize($amount), $percent, 4), '100', 2);
    }

    public static function cmp(string $left, string $right): int
    {
        return bccomp(self::normalize($left), self::normalize($right), 2);
    }
}
