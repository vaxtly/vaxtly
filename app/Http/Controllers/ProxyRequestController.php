<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class ProxyRequestController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'method' => 'required|string',
            'url' => 'required|string',
            'headers' => 'nullable|array',
            'timeout' => 'nullable|integer|min:1|max:300',
            'options' => 'nullable|array',
        ]);

        $timeout = $validated['timeout'] ?? 30;
        $headers = $validated['headers'] ?? [];
        $options = $validated['options'] ?? [];

        // Resolve query params into URL
        $url = $validated['url'];
        if (! empty($options['query'])) {
            $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($options['query']);
        }

        // Resolve body and content-type from Guzzle-style options
        $body = '';
        if (isset($options['json'])) {
            $body = json_encode($options['json'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $headers['Content-Type'] ??= 'application/json';
        } elseif (isset($options['form_params'])) {
            $body = http_build_query($options['form_params']);
            $headers['Content-Type'] ??= 'application/x-www-form-urlencoded';
        } elseif (isset($options['body'])) {
            $body = $options['body'];
        }

        $workerConfig = [
            'method' => strtoupper($validated['method']),
            'url' => $url,
            'headers' => $headers,
            'body' => $body,
            'timeout' => $timeout,
        ];

        $process = new Process([PHP_BINARY, base_path('app/Support/proxy-worker.php')]);
        $process->setInput(json_encode($workerConfig));
        $process->setTimeout($timeout + 10);
        $process->start();

        return response()->stream(function () use ($process): void {
            ignore_user_abort(true);

            // Flush all output buffers so echo+flush reaches the client
            while (ob_get_level()) {
                ob_end_flush();
            }

            while ($process->isRunning()) {
                echo ' ';
                flush();

                if (connection_aborted()) {
                    $process->stop(0);

                    return;
                }

                usleep(50000); // 50ms
            }

            $output = $process->getOutput();
            if ($process->getExitCode() !== 0 && empty($output)) {
                echo json_encode(['error' => $process->getErrorOutput() ?: 'Request failed']);
            } else {
                echo $output;
            }
        }, 200, [
            'Content-Type' => 'text/plain',
            'X-Accel-Buffering' => 'no',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
