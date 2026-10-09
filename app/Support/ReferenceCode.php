<?php

namespace App\Support;

final class ReferenceCode
{
    public static function make(string $prefix, int $id): string
    {
        return $prefix.'-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }
}
