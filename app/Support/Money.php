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

    public static function normalizeRate(string|int|float|null $value): string
    {
        if ($value === null || $value === '') {
            return '0.0000';
        }

        return bcadd((string) $value, '0', 4);
    }

    public static function mulRate(string $amount, string $rate): string
    {
        return self::roundHalfUp(bcmul(self::normalize($amount), self::normalizeRate($rate), 6));
    }

    public static function divRate(string $amount, string $rate): string
    {
        $normalizedRate = self::normalizeRate($rate);

        if (bccomp($normalizedRate, '0', 4) <= 0) {
            return '0.00';
        }

        return self::roundHalfUp(bcdiv(self::normalize($amount), $normalizedRate, 6));
    }

    public static function roundHalfUp(string $value, int $scale = 2): string
    {
        $negative = str_starts_with($value, '-');
        $absolute = ltrim($value, '-');

        if (! str_contains($absolute, '.')) {
            $absolute .= '.'.str_repeat('0', $scale + 1);
        }

        [$whole, $decimals] = explode('.', $absolute, 2);
        $decimals = str_pad($decimals, $scale + 1, '0');
        $kept = substr($decimals, 0, $scale);
        $next = (int) ($decimals[$scale] ?? '0');
        $base = bcadd($whole.'.'.str_pad($kept, $scale, '0'), '0', $scale);

        if ($next >= 5) {
            $base = bcadd($base, '0.'.str_pad('1', $scale, '0', STR_PAD_LEFT), $scale);
        }

        return $negative && bccomp($base, '0', $scale) !== 0 ? '-'.$base : $base;
    }
}
