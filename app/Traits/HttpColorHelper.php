<?php

namespace App\Traits;

trait HttpColorHelper
{
    public function getMethodColor(string $method): string
    {
        return match (strtoupper($method)) {
            'GET' => 'text-emerald-600 dark:text-emerald-400',
            'POST' => 'text-blue-600 dark:text-blue-400',
            'PUT', 'PATCH' => 'text-amber-600 dark:text-amber-400',
            'DELETE' => 'text-red-600 dark:text-red-400',
            default => 'text-gray-600 dark:text-gray-400',
        };
    }

    public function getMethodBadgeColor(string $method): string
    {
        return match (strtoupper($method)) {
            'GET' => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400',
            'POST' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400',
            'PUT', 'PATCH' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-400',
            'DELETE' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-400',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        };
    }

    public function getStatusColor(?int $code = null): string
    {
        if (! $code) {
            return '';
        }

        return match (true) {
            $code >= 200 && $code < 300 => 'text-green-600 dark:text-green-400',
            $code >= 300 && $code < 400 => 'text-blue-600 dark:text-blue-400',
            $code >= 400 && $code < 500 => 'text-yellow-600 dark:text-yellow-400',
            $code >= 500 => 'text-red-600 dark:text-red-400',
            default => 'text-gray-600 dark:text-gray-400',
        };
    }

    public function colorizeJson(string $json): string
    {
        if (empty($json)) {
            return '';
        }

        $escaped = htmlspecialchars($json, ENT_QUOTES | ENT_HTML5);

        $patterns = [
            // String values (green)
            '/"([^"\\\\]|\\\\.)*"(?=\s*[,\}\]])/' => '<span class="text-green-600 dark:text-green-400">$0</span>',
            // Property names (purple)
            '/"([^"\\\\]|\\\\.)*"(?=\s*:)/' => '<span class="text-purple-600 dark:text-purple-400">$0</span>',
            // Numbers (orange)
            '/\b(-?\d+\.?\d*)\b/' => '<span class="text-orange-500 dark:text-orange-400">$1</span>',
            // Booleans and null (blue)
            '/\b(true|false|null)\b/' => '<span class="text-blue-600 dark:text-blue-400">$1</span>',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $escaped = preg_replace($pattern, $replacement, $escaped);
        }

        return $escaped;
    }
}
