<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Request;
use Illuminate\Support\Facades\Http;

class ScriptExecutionService
{
    private const MAX_CHAIN_DEPTH = 3;

    /** @var array<int, string> */
    private array $executionStack = [];

    /**
     * Execute pre-request scripts for a request.
     * Returns false if execution should abort the main request.
     */
    public function executePreRequestScripts(Request $request, VariableSubstitutionService $substitutionService): bool
    {
        $scripts = $request->getPreRequestScripts();

        if (empty($scripts)) {
            return true;
        }

        foreach ($scripts as $script) {
            if (($script['action'] ?? '') !== 'send_request') {
                continue;
            }

            $requestId = $script['request_id'] ?? null;
            if (! $requestId) {
                continue;
            }

            $this->executeDependentRequest($requestId, $request->collection_id, $substitutionService);
        }

        return true;
    }

    /**
     * Execute post-response scripts for a request.
     *
     * @param  array<string, mixed>  $headers
     */
    public function executePostResponseScripts(Request $request, int $statusCode, ?string $body, array $headers): void
    {
        $scripts = $request->getPostResponseScripts();

        if (empty($scripts)) {
            return;
        }

        foreach ($scripts as $script) {
            if (($script['action'] ?? '') !== 'set_variable') {
                continue;
            }

            $source = $script['source'] ?? null;
            $target = $script['target'] ?? null;
            $collectionId = $request->collection_id;

            if (! $source || ! $target || ! $collectionId) {
                continue;
            }

            $value = $this->extractValue($source, $statusCode, $body, $headers);

            if ($value !== null) {
                $this->setCollectionVariable($collectionId, $target, $value);
            }
        }
    }

    /**
     * Execute a dependent request (used by pre-request scripts).
     * Runs the dependent request's own pre/post scripts recursively.
     */
    public function executeDependentRequest(string $requestId, string $collectionId, VariableSubstitutionService $substitutionService): void
    {
        if (in_array($requestId, $this->executionStack)) {
            throw new \RuntimeException('Circular dependency detected in request scripts');
        }

        if (count($this->executionStack) >= self::MAX_CHAIN_DEPTH) {
            throw new \RuntimeException('Maximum script chain depth ('.self::MAX_CHAIN_DEPTH.') exceeded');
        }

        $request = Request::where('id', $requestId)
            ->where('collection_id', $collectionId)
            ->first();

        if (! $request) {
            throw new \RuntimeException("Dependent request [{$requestId}] not found in this collection");
        }

        $this->executionStack[] = $requestId;

        try {
            // Recursively run this request's pre-request scripts
            $this->executePreRequestScripts($request, $substitutionService);

            // Execute the HTTP request
            [$statusCode, $body, $headers] = $this->executeHttpRequest($request, $substitutionService);

            // Run post-response scripts
            $this->executePostResponseScripts($request, $statusCode, $body, $headers);
        } finally {
            array_pop($this->executionStack);
        }
    }

    /**
     * Execute an HTTP request using persisted model data.
     *
     * @return array{0: int, 1: string|null, 2: array<string, mixed>}
     */
    public function executeHttpRequest(Request $request, VariableSubstitutionService $substitutionService): array
    {
        $collectionId = $request->collection_id;

        $url = $substitutionService->substitute($request->url, $collectionId);

        $headers = $substitutionService->substituteArray($request->headers ?? [], $collectionId);

        $queryParams = $substitutionService->substituteArray($request->query_params ?? [], $collectionId);

        $client = Http::withHeaders($headers)->timeout(30);

        if ($request->body_type === 'urlencoded') {
            $client = $client->asForm();
        }

        $bodyWithVars = $substitutionService->substitute($request->body ?? '', $collectionId);

        $requestBody = match ($request->body_type) {
            'json' => json_decode($bodyWithVars, true) ?? [],
            'form-data', 'urlencoded' => $this->parseFormBody($bodyWithVars, $substitutionService, $collectionId),
            'raw' => $bodyWithVars,
            default => null,
        };

        $options = [];

        if (! empty($queryParams)) {
            $options['query'] = $queryParams;
        }

        if ($requestBody !== null) {
            $bodyFormatKey = $request->body_type === 'urlencoded' ? 'form_params' : 'json';
            $options[$bodyFormatKey] = $requestBody;
        }

        $httpResponse = $client->send(strtoupper($request->method), $url, $options);

        return [
            $httpResponse->status(),
            $httpResponse->body(),
            $httpResponse->headers(),
        ];
    }

    /**
     * Extract a value from a response using a source expression.
     *
     * Supported expressions:
     * - body.key.nested[0].id
     * - header.Name
     * - status
     *
     * @param  array<string, mixed>  $headers
     */
    public function extractValue(string $source, int $statusCode, ?string $body, array $headers): ?string
    {
        if ($source === 'status') {
            return (string) $statusCode;
        }

        if (str_starts_with($source, 'header.')) {
            $headerName = substr($source, 7);

            // Case-insensitive header lookup
            foreach ($headers as $key => $value) {
                if (strcasecmp($key, $headerName) === 0) {
                    return is_array($value) ? ($value[0] ?? null) : $value;
                }
            }

            return null;
        }

        if (str_starts_with($source, 'body.')) {
            $path = substr($source, 5);

            $decoded = json_decode($body ?? '', true);
            if (! is_array($decoded)) {
                return null;
            }

            return $this->extractJsonPath($decoded, $path);
        }

        return null;
    }

    /**
     * Extract a value from a JSON structure using dot-notation with array index support.
     *
     * @param  array<string, mixed>  $data
     */
    public function extractJsonPath(array $data, string $path): ?string
    {
        // Parse path segments: "data.items[0].name" => ["data", "items", "0", "name"]
        $segments = [];
        foreach (explode('.', $path) as $part) {
            // Handle array brackets like items[0]
            if (preg_match('/^(.+?)\[(\d+)\]$/', $part, $matches)) {
                $segments[] = $matches[1];
                $segments[] = (int) $matches[2];
            } else {
                $segments[] = $part;
            }
        }

        $current = $data;
        foreach ($segments as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
            } else {
                return null;
            }
        }

        if (is_array($current)) {
            return json_encode($current);
        }

        return $current === null ? null : (string) $current;
    }

    /**
     * Set a collection variable, adding or updating it.
     */
    public function setCollectionVariable(string $collectionId, string $key, string $value): void
    {
        $collection = Collection::find($collectionId);
        if (! $collection) {
            return;
        }

        $variables = $collection->variables ?? [];
        $found = false;

        foreach ($variables as &$variable) {
            if (($variable['key'] ?? '') === $key) {
                $variable['value'] = $value;
                $variable['enabled'] = true;
                $found = true;
                break;
            }
        }
        unset($variable);

        if (! $found) {
            $variables[] = [
                'key' => $key,
                'value' => $value,
                'enabled' => true,
            ];
        }

        $collection->update(['variables' => $variables]);
    }

    /**
     * Parse form body data from stored JSON format.
     *
     * @return array<string, string>
     */
    private function parseFormBody(string $bodyJson, VariableSubstitutionService $substitutionService, ?string $collectionId): array
    {
        $decoded = json_decode($bodyJson, true);
        if (! is_array($decoded)) {
            return [];
        }

        $result = [];
        foreach ($decoded as $field) {
            $key = $field['key'] ?? '';
            if (! empty($key)) {
                $result[$substitutionService->substitute($key, $collectionId)] = $substitutionService->substitute($field['value'] ?? '', $collectionId);
            }
        }

        return $result;
    }
}
