<?php

/**
 * Standalone HTTP request worker script.
 * Reads JSON config from stdin, makes request using PHP streams, outputs JSON result.
 * No framework or extensions required.
 */
$config = json_decode(file_get_contents('php://stdin'), true);

if (! $config || empty($config['url'])) {
    echo json_encode(['error' => 'Invalid request config']);
    exit(1);
}

$headers = [];
foreach ($config['headers'] ?? [] as $key => $value) {
    $headers[] = "$key: $value";
}

$context = stream_context_create([
    'http' => [
        'method' => $config['method'] ?? 'GET',
        'header' => implode("\r\n", $headers),
        'content' => $config['body'] ?? '',
        'timeout' => $config['timeout'] ?? 30,
        'ignore_errors' => true,
        'follow_location' => 1,
        'max_redirects' => 5,
    ],
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
    ],
]);

$body = @file_get_contents($config['url'], false, $context);
$responseHeaders = $http_response_header ?? [];

// Parse status code from last HTTP line (handles redirects)
$statusCode = 0;
$lastHttpIndex = 0;
foreach ($responseHeaders as $index => $line) {
    if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $line, $matches)) {
        $statusCode = (int) $matches[1];
        $lastHttpIndex = $index;
    }
}

// If no response at all, report the error
if ($body === false && $statusCode === 0) {
    $error = error_get_last();
    echo json_encode(['error' => $error['message'] ?? 'Request failed'], JSON_INVALID_UTF8_SUBSTITUTE);
    exit(1);
}

// Parse headers from final response only
$parsedHeaders = [];
foreach (array_slice($responseHeaders, $lastHttpIndex + 1) as $line) {
    $parts = explode(':', $line, 2);
    if (count($parts) === 2) {
        $parsedHeaders[trim($parts[0])][] = trim($parts[1]);
    }
}

echo json_encode([
    'status' => $statusCode,
    'body' => $body === false ? '' : $body,
    'headers' => $parsedHeaders,
], JSON_INVALID_UTF8_SUBSTITUTE);
