<?php

namespace App\Support;

class BootLogger
{
    private static ?float $startTime = null;

    private static ?float $lastTime = null;

    private static string $logFile = '';

    public static function start(): void
    {
        static::$logFile = storage_path('logs/vaxtly.log');
        static::$startTime = microtime(true);
        static::$lastTime = static::$startTime;

        file_put_contents(static::$logFile, static::formatLine('Boot started')."\n", FILE_APPEND);
    }

    public static function log(string $message): void
    {
        if (static::$startTime === null) {
            return;
        }

        $now = microtime(true);

        file_put_contents(static::$logFile, static::formatLine($message, $now)."\n", FILE_APPEND);

        static::$lastTime = $now;
    }

    private static function formatLine(string $message, ?float $now = null): string
    {
        $now ??= static::$startTime;

        $cumulativeMs = round(($now - static::$startTime) * 1000);
        $deltaMs = round(($now - static::$lastTime) * 1000);

        $timestamp = date('Y-m-d H:i:s').'.'.substr(sprintf('%06d', (int) (fmod($now, 1) * 1_000_000)), 0, 3);

        return "[{$timestamp}] [boot +{$cumulativeMs}ms] [{$deltaMs}ms] {$message}";
    }
}
