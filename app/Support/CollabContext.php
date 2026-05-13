<?php

namespace App\Support;

class CollabContext
{
    private static ?int $hostId = null;

    public static function set(?int $hostId): void
    {
        self::$hostId = $hostId;
    }

    public static function hostId(): ?int
    {
        return self::$hostId;
    }

    public static function active(): bool
    {
        return self::$hostId !== null;
    }
}
