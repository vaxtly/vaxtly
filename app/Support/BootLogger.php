<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class BootLogger
{
    private static ?float $startTime = null;

    private static ?float $lastTime = null;

    public static function start(): void
    {
        static::$startTime = microtime(true);
        static::$lastTime = static::$startTime;

        Log::info(static::formatMessage('Boot started'));
    }

    public static function log(string $message): void
    {
        if (static::$startTime === null) {
            return;
        }

        $now = microtime(true);

        Log::info(static::formatMessage($message, $now));

        static::$lastTime = $now;
    }

    private static function formatMessage(string $message, ?float $now = null): string
    {
        $now ??= static::$startTime;

        $cumulativeMs = round(($now - static::$startTime) * 1000);
        $deltaMs = round(($now - static::$lastTime) * 1000);

        return "[boot +{$cumulativeMs}ms] [{$deltaMs}ms] {$message}";
    }
}
