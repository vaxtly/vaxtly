<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;

class SessionLogService
{
    private const SESSION_KEY = 'system_logs';
    private const MAX_LOGS = 100;

    /**
     * Log a Git operation (push/pull).
     */
    public function logGitOperation(
        string $type,
        ?string $collectionName,
        string $message,
        bool $success = true
    ): void {
        $this->addLog([
            'category' => 'git',
            'type' => $type,
            'target' => $collectionName,
            'message' => $message,
            'success' => $success,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log a Vault operation (read/write).
     */
    public function logVaultOperation(
        string $type,
        ?string $path,
        string $message,
        bool $success = true
    ): void {
        $this->addLog([
            'category' => 'vault',
            'type' => $type,
            'target' => $path,
            'message' => $message,
            'success' => $success,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get all system logs for the current session.
     *
     * @return array<int, array{category: string, type: string, target: ?string, message: string, success: bool, timestamp: string}>
     */
    public function getSystemLogs(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    /**
     * Clear all system logs.
     */
    public function clearLogs(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Add a log entry to the session.
     */
    private function addLog(array $entry): void
    {
        $logs = $this->getSystemLogs();
        array_unshift($logs, $entry);
        
        // Keep only the most recent logs
        if (count($logs) > self::MAX_LOGS) {
            $logs = array_slice($logs, 0, self::MAX_LOGS);
        }

        Session::put(self::SESSION_KEY, $logs);
    }
}
