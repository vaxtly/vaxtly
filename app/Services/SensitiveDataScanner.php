<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Request;

class SensitiveDataScanner
{
    /** @var array<int, string> */
    protected const SENSITIVE_HEADER_KEYS = [
        'authorization',
        'proxy-authorization',
        'x-api-key',
        'x-auth-token',
        'x-access-token',
        'x-secret-key',
        'x-csrf-token',
        'x-xsrf-token',
        'x-token',
        'cookie',
        'set-cookie',
    ];

    /** @var array<int, string> */
    protected const SENSITIVE_PARAM_KEYS = [
        // Auth tokens
        'token',
        'access_token',
        'accesstoken',
        'auth_token',
        'authtoken',
        'refresh_token',
        'refreshtoken',
        'bearer_token',
        'id_token',
        'session_token',
        'sessiontoken',
        'jwt',
        'jwt_token',
        'oauth_token',
        'csrf_token',
        'xsrf_token',
        // API keys
        'api_key',
        'apikey',
        'api-key',
        'api_secret',
        'apisecret',
        'app_key',
        'appkey',
        'app_secret',
        'appsecret',
        'consumer_key',
        'consumer_secret',
        'master_key',
        'masterkey',
        // Passwords & secrets
        'password',
        'passwd',
        'pass',
        'secret',
        'secret_key',
        'secretkey',
        'private_key',
        'privatekey',
        'signing_key',
        'encryption_key',
        'hmac_key',
        'hmac_secret',
        'webhook_secret',
        'client_secret',
        'client_id',
        // Keys (generic)
        'key',
        'credentials',
        'credential',
        // Session / identity
        'session_id',
        'sessionid',
        'sid',
        'pin',
        'otp',
        'totp',
        'totp_secret',
        'recovery_code',
        // Database
        'db_password',
        'database_password',
        'connection_string',
        // Cloud / service-specific
        'aws_secret_access_key',
        'aws_access_key_id',
        'stripe_key',
        'stripe_secret',
        'twilio_auth_token',
        'sendgrid_api_key',
        'slack_token',
        'github_token',
        'gitlab_token',
        'heroku_api_key',
        'firebase_api_key',
        // Financial / PII
        'ssn',
        'credit_card',
        'card_number',
        'cvv',
        'cvc',
        'account_number',
        'routing_number',
    ];

    /**
     * Scan a collection for plain-text sensitive values.
     *
     * @return array<int, array{source: string, request_name: string|null, request_id: string|null, field: string, key: string, masked_value: string}>
     */
    public function scanCollection(Collection $collection): array
    {
        $findings = [];

        $requests = Request::where('collection_id', $collection->id)->get();

        foreach ($requests as $request) {
            $findings = array_merge($findings, $this->scanRequest($request));
        }

        $referencedVars = $this->collectReferencedVariables($requests);
        $findings = array_merge($findings, $this->scanCollectionVariables($collection, $referencedVars));

        return $findings;
    }

    /**
     * Scan a single request for plain-text sensitive values.
     *
     * @return array<int, array{source: string, request_name: string|null, request_id: string|null, field: string, key: string, masked_value: string}>
     */
    public function scanRequest(Request $request): array
    {
        return array_merge(
            $this->scanAuth($request),
            $this->scanHeaders($request),
            $this->scanQueryParams($request),
            $this->scanBody($request),
        );
    }

    /**
     * Sanitize a request data array (same shape as YamlCollectionSerializer::serializeRequest builds).
     * Blanks sensitive values to empty strings, preserving {{...}} references.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function sanitizeRequestData(array $data): array
    {
        // Sanitize auth
        if (! empty($data['auth']) && is_array($data['auth'])) {
            $data['auth'] = $this->sanitizeAuthData($data['auth']);
        }

        // Sanitize headers
        if (! empty($data['headers']) && is_array($data['headers'])) {
            $data['headers'] = $this->sanitizeKeyValuePairs($data['headers'], self::SENSITIVE_HEADER_KEYS);
        }

        // Sanitize query params
        if (! empty($data['query_params']) && is_array($data['query_params'])) {
            $data['query_params'] = $this->sanitizeKeyValuePairs($data['query_params'], self::SENSITIVE_PARAM_KEYS);
        }

        // Sanitize body
        if (! empty($data['body'])) {
            $data['body'] = $this->sanitizeBodyData($data['body'], $data['body_type'] ?? 'none');
        }

        return $data;
    }

    /**
     * Sanitize collection-level data array (variables).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function sanitizeCollectionData(array $data): array
    {
        if (empty($data['variables']) || ! is_array($data['variables'])) {
            return $data;
        }

        $allSensitiveKeys = array_merge(self::SENSITIVE_HEADER_KEYS, self::SENSITIVE_PARAM_KEYS);
        $data['variables'] = $this->sanitizeKeyValuePairs($data['variables'], $allSensitiveKeys);

        return $data;
    }

    public function isVariableReference(string $value): bool
    {
        return (bool) preg_match('/\{\{.+?\}\}/', $value);
    }

    public function maskValue(string $value): string
    {
        if (strlen($value) <= 4) {
            return $value;
        }

        return substr($value, 0, 4).str_repeat('*', min(strlen($value) - 4, 8));
    }

    /**
     * @return array<int, array{source: string, request_name: string|null, request_id: string|null, field: string, key: string, masked_value: string}>
     */
    protected function scanAuth(Request $request): array
    {
        $auth = $request->auth;
        if (empty($auth) || empty($auth['type']) || $auth['type'] === 'none') {
            return [];
        }

        $findings = [];

        match ($auth['type']) {
            'bearer' => $this->checkAuthField($auth, 'token', 'bearer token', $request, $findings),
            'basic' => $this->checkAuthField($auth, 'password', 'basic password', $request, $findings),
            'api-key' => $this->checkAuthField($auth, 'api_key_value', 'api-key value', $request, $findings),
            default => null,
        };

        return $findings;
    }

    /**
     * @param  array<string, mixed>  $auth
     * @param  array<int, array{source: string, request_name: string|null, request_id: string|null, field: string, key: string, masked_value: string}>  &$findings
     */
    protected function checkAuthField(array $auth, string $field, string $label, Request $request, array &$findings): void
    {
        $value = $auth[$field] ?? '';
        if (is_string($value) && $value !== '' && ! $this->isVariableReference($value)) {
            $findings[] = [
                'source' => 'auth',
                'request_name' => $request->name,
                'request_id' => $request->id,
                'field' => 'auth',
                'key' => $label,
                'masked_value' => $this->maskValue($value),
            ];
        }
    }

    /**
     * @return array<int, array{source: string, request_name: string|null, request_id: string|null, field: string, key: string, masked_value: string}>
     */
    protected function scanHeaders(Request $request): array
    {
        return $this->scanKeyValueData(
            $request->headers ?? [],
            self::SENSITIVE_HEADER_KEYS,
            'header',
            'headers',
            $request,
        );
    }

    /**
     * @return array<int, array{source: string, request_name: string|null, request_id: string|null, field: string, key: string, masked_value: string}>
     */
    protected function scanQueryParams(Request $request): array
    {
        return $this->scanKeyValueData(
            $request->query_params ?? [],
            self::SENSITIVE_PARAM_KEYS,
            'param',
            'query_params',
            $request,
        );
    }

    /**
     * @return array<int, array{source: string, request_name: string|null, request_id: string|null, field: string, key: string, masked_value: string}>
     */
    protected function scanBody(Request $request): array
    {
        $body = $request->body;
        if (empty($body)) {
            return [];
        }

        // form-data / urlencoded: structured key-value pairs
        if (in_array($request->body_type, ['form-data', 'urlencoded'], true)) {
            $data = is_string($body) ? json_decode($body, true) : $body;
            if (! is_array($data)) {
                return [];
            }

            return $this->scanKeyValueData(
                $data,
                self::SENSITIVE_PARAM_KEYS,
                'body',
                'body',
                $request,
            );
        }

        // JSON (or any other string body): try to decode and recursively scan
        if (is_string($body)) {
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                return $this->scanJsonRecursive($decoded, $request);
            }
        }

        return [];
    }

    /**
     * Recursively scan a decoded JSON structure for sensitive keys.
     *
     * @param  array<mixed>  $data
     * @return array<int, array{source: string, request_name: string|null, request_id: string|null, field: string, key: string, masked_value: string}>
     */
    protected function scanJsonRecursive(array $data, Request $request): array
    {
        $findings = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $findings = array_merge($findings, $this->scanJsonRecursive($value, $request));

                continue;
            }

            if (! is_string($key) || ! is_string($value) || $value === '') {
                continue;
            }

            if ($this->isVariableReference($value)) {
                continue;
            }

            if ($this->isSensitiveKey($key, self::SENSITIVE_PARAM_KEYS)) {
                $findings[] = [
                    'source' => 'body',
                    'request_name' => $request->name,
                    'request_id' => $request->id,
                    'field' => 'body',
                    'key' => $key,
                    'masked_value' => $this->maskValue($value),
                ];
            }
        }

        return $findings;
    }

    /**
     * Scan key-value data that may be in flat or structured format.
     *
     * @param  array<mixed>  $data
     * @param  array<int, string>  $sensitiveKeys
     * @return array<int, array{source: string, request_name: string|null, request_id: string|null, field: string, key: string, masked_value: string}>
     */
    protected function scanKeyValueData(
        array $data,
        array $sensitiveKeys,
        string $source,
        string $field,
        Request $request,
    ): array {
        $findings = [];
        $normalized = $this->normalizeKeyValuePairs($data);

        foreach ($normalized as $pair) {
            $key = $pair['key'] ?? '';
            $value = $pair['value'] ?? '';

            if (! is_string($key) || ! is_string($value) || $value === '') {
                continue;
            }

            if ($this->isVariableReference($value)) {
                continue;
            }

            if ($this->isSensitiveKey($key, $sensitiveKeys)) {
                $findings[] = [
                    'source' => $source,
                    'request_name' => $request->name,
                    'request_id' => $request->id,
                    'field' => $field,
                    'key' => $key,
                    'masked_value' => $this->maskValue($value),
                ];
            }
        }

        return $findings;
    }

    /**
     * @return array<int, array{source: string, request_name: string|null, request_id: string|null, field: string, key: string, masked_value: string}>
     */
    /**
     * Collect all variable names referenced via {{...}} in request data.
     *
     * @param  \Illuminate\Support\Collection<int, Request>  $requests
     * @return array<int, string>
     */
    protected function collectReferencedVariables($requests): array
    {
        $vars = [];

        foreach ($requests as $request) {
            $haystack = json_encode([
                $request->url,
                $request->headers,
                $request->query_params,
                $request->body,
                $request->auth,
            ]);

            preg_match_all('/\{\{(.+?)\}\}/', $haystack, $matches);
            $vars = array_merge($vars, $matches[1]);
        }

        return array_unique($vars);
    }

    /**
     * @param  array<int, string>  $referencedVars  Variable names used as {{...}} in requests
     * @return array<int, array{source: string, request_name: string|null, request_id: string|null, field: string, key: string, masked_value: string}>
     */
    protected function scanCollectionVariables(Collection $collection, array $referencedVars = []): array
    {
        $findings = [];
        $allSensitiveKeys = array_merge(self::SENSITIVE_HEADER_KEYS, self::SENSITIVE_PARAM_KEYS);

        foreach ($collection->variables ?? [] as $variable) {
            $key = $variable['key'] ?? '';
            $value = $variable['value'] ?? '';

            if (! is_string($key) || ! is_string($value) || $value === '') {
                continue;
            }

            if ($this->isVariableReference($value)) {
                continue;
            }

            // Skip variables actively used as {{name}} references — their values
            // are dynamic (often set by pre-request scripts) and stale stored
            // values shouldn't block sync.
            if (in_array($key, $referencedVars, true)) {
                continue;
            }

            if ($this->isSensitiveKey($key, $allSensitiveKeys)) {
                $findings[] = [
                    'source' => 'variable',
                    'request_name' => null,
                    'request_id' => null,
                    'field' => 'variables',
                    'key' => $key,
                    'masked_value' => $this->maskValue($value),
                ];
            }
        }

        return $findings;
    }

    /**
     * Normalize flat {"key": "value"} and structured [{"key": "...", "value": "..."}] formats.
     *
     * @param  array<mixed>  $data
     * @return array<int, array{key: string, value: string}>
     */
    protected function normalizeKeyValuePairs(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        // Check if this is a structured array (list of objects with key/value)
        if (array_is_list($data)) {
            $pairs = [];
            foreach ($data as $item) {
                if (is_array($item) && isset($item['key'])) {
                    $pairs[] = [
                        'key' => (string) ($item['key'] ?? ''),
                        'value' => (string) ($item['value'] ?? ''),
                    ];
                }
            }

            return $pairs;
        }

        // Flat format: {"key": "value"}
        $pairs = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $pairs[] = ['key' => (string) $key, 'value' => $value];
            }
        }

        return $pairs;
    }

    /**
     * @param  array<int, string>  $sensitiveKeys
     */
    protected function isSensitiveKey(string $key, array $sensitiveKeys): bool
    {
        $lowerKey = strtolower($key);

        return in_array($lowerKey, $sensitiveKeys, true);
    }

    /**
     * Sanitize auth data by blanking sensitive values.
     *
     * @param  array<string, mixed>  $auth
     * @return array<string, mixed>
     */
    private function sanitizeAuthData(array $auth): array
    {
        $type = $auth['type'] ?? 'none';

        $fieldsToSanitize = match ($type) {
            'bearer' => ['token'],
            'basic' => ['password'],
            'api-key' => ['api_key_value'],
            default => [],
        };

        foreach ($fieldsToSanitize as $field) {
            $value = $auth[$field] ?? '';
            if (is_string($value) && $value !== '' && ! $this->isVariableReference($value)) {
                $auth[$field] = '';
            }
        }

        return $auth;
    }

    /**
     * Sanitize key-value pair data (flat or structured format).
     *
     * @param  array<mixed>  $data
     * @param  array<int, string>  $sensitiveKeys
     * @return array<mixed>
     */
    private function sanitizeKeyValuePairs(array $data, array $sensitiveKeys): array
    {
        if (empty($data)) {
            return $data;
        }

        // Structured format: [['key' => '...', 'value' => '...'], ...]
        if (array_is_list($data)) {
            return array_map(function ($item) use ($sensitiveKeys) {
                if (! is_array($item) || ! isset($item['key'])) {
                    return $item;
                }

                $key = (string) ($item['key'] ?? '');
                $value = (string) ($item['value'] ?? '');

                if ($value !== '' && ! $this->isVariableReference($value) && $this->isSensitiveKey($key, $sensitiveKeys)) {
                    $item['value'] = '';
                }

                return $item;
            }, $data);
        }

        // Flat format: ['key' => 'value']
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($value) && $value !== '' && ! $this->isVariableReference($value) && $this->isSensitiveKey((string) $key, $sensitiveKeys)) {
                $result[$key] = '';
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Sanitize body data based on body type.
     */
    private function sanitizeBodyData(mixed $body, string $bodyType): mixed
    {
        // form-data / urlencoded: structured key-value pairs
        if (in_array($bodyType, ['form-data', 'urlencoded'], true)) {
            $data = is_string($body) ? json_decode($body, true) : $body;
            if (is_array($data)) {
                $sanitized = $this->sanitizeKeyValuePairs($data, self::SENSITIVE_PARAM_KEYS);

                return is_string($body) ? json_encode($sanitized) : $sanitized;
            }

            return $body;
        }

        // JSON body
        if (is_string($body)) {
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                $sanitized = $this->sanitizeJsonRecursive($decoded);

                return json_encode($sanitized);
            }
        }

        return $body;
    }

    /**
     * Recursively sanitize a decoded JSON structure.
     *
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    private function sanitizeJsonRecursive(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeJsonRecursive($value);

                continue;
            }

            if (! is_string($key) || ! is_string($value) || $value === '') {
                continue;
            }

            if ($this->isVariableReference($value)) {
                continue;
            }

            if ($this->isSensitiveKey($key, self::SENSITIVE_PARAM_KEYS)) {
                $data[$key] = '';
            }
        }

        return $data;
    }
}
