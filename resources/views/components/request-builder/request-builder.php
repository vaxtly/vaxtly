<?php

use App\Models\Collection;
use App\Models\Request;
use App\Models\RequestHistory;
use App\Services\ScriptExecutionService;
use App\Services\SensitiveDataScanner;
use App\Services\VariableSubstitutionService;
use App\Traits\HttpColorHelper;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Component;

new class extends Component
{
    use HttpColorHelper;

    public ?string $activeTabId = null;

    public array $tabStates = [];

    public $requestId = null;

    public $name = '';

    public $url = '';

    public $method = 'GET';

    public $headers = [['key' => '', 'value' => '']];

    public $queryParams = [['key' => '', 'value' => '']];

    public $body = '';

    public $bodyType = 'none';

    public $formData = [['key' => '', 'value' => '']];

    // Auth properties
    public $authType = 'none';

    public $authToken = '';

    public $authUsername = '';

    public $authPassword = '';

    public $apiKeyName = '';

    public $apiKeyValue = '';

    public $response = null;

    public $statusCode = null;

    public $duration = null;

    public $responseHeaders = [];

    public $isLoading = false;

    public $error = null;

    public string $collectionName = '';

    public $selectedCollectionId = null;

    public ?string $folderName = null;

    public array $collectionRequests = [];

    public array $preRequestScripts = [];

    public array $postResponseScripts = [];

    public bool $showCodeModal = false;

    public string $codeLanguage = 'curl';

    // Sync sensitive data modal
    public bool $showSyncSensitiveModal = false;

    public ?string $pendingSyncRequestId = null;

    public array $pendingSyncFindings = [];

    public string $layout = 'columns'; // 'rows' or 'columns'

    public function mount(?string $initialActiveTabId = null, ?string $initialRequestId = null): void
    {
        $this->activeTabId = $initialActiveTabId;
        $this->layout = get_setting('requests.layout', 'columns');

        // If a requestId is provided on mount, load it immediately
        if ($initialRequestId) {
            $this->loadRequest($initialRequestId);
        }
    }

    #[On('layout-updated')]
    public function updateLayout(?string $layout = null): void
    {
        $this->layout = $layout ?? get_setting('requests.layout', 'columns');
    }

    #[On('switch-tab')]
    #[Renderless]
    public function switchTab(string $tabId, string $requestId): void
    {
        // Save current tab state
        if ($this->activeTabId && $this->requestId) {
            $this->tabStates[$this->activeTabId] = $this->getCurrentState();
        }

        $this->activeTabId = $tabId;

        // Check if we have cached state for this tab
        if (isset($this->tabStates[$tabId])) {
            $this->restoreState($this->tabStates[$tabId]);
            $this->refreshCollectionRequests();
        } else {
            // Load fresh from database (loadRequest already calls refreshCollectionRequests)
            $this->loadRequest($requestId);
        }
    }

    #[On('close-tab')]
    public function closeTab(string $tabId): void
    {
        unset($this->tabStates[$tabId]);
    }

    private function getCurrentState(): array
    {
        return [
            'requestId' => $this->requestId,
            'name' => $this->name,
            'url' => $this->url,
            'method' => $this->method,
            'headers' => $this->headers,
            'queryParams' => $this->queryParams,
            'body' => $this->body,
            'bodyType' => $this->bodyType,
            'formData' => $this->formData,
            'authType' => $this->authType,
            'authToken' => $this->authToken,
            'authUsername' => $this->authUsername,
            'authPassword' => $this->authPassword,
            'apiKeyName' => $this->apiKeyName,
            'apiKeyValue' => $this->apiKeyValue,
            'selectedCollectionId' => $this->selectedCollectionId,
            'collectionName' => $this->collectionName,
            'folderName' => $this->folderName,
            'preRequestScripts' => $this->preRequestScripts,
            'postResponseScripts' => $this->postResponseScripts,
            'response' => $this->response,
            'statusCode' => $this->statusCode,
            'duration' => $this->duration,
            'responseHeaders' => $this->responseHeaders,
            'error' => $this->error,
        ];
    }

    private function restoreState(array $state): void
    {
        foreach ($state as $key => $value) {
            $this->$key = $value;
        }
    }

    public function loadRequest(string $requestId): void
    {
        $request = Request::with(['folder', 'collection:id,name'])->find($requestId);

        if (! $request) {
            return;
        }

        $this->requestId = $request->id;
        $this->name = $request->name;
        $this->folderName = $request->folder?->name;
        $this->url = $request->url;
        $this->method = $request->method;
        $this->body = $request->body ?? '';
        $this->bodyType = $request->body_type;

        if (in_array($this->bodyType, ['form-data', 'urlencoded'])) {
            $decoded = json_decode($this->body, true);
            $this->formData = is_array($decoded) ? $decoded : [['key' => '', 'value' => '']];
        } else {
            $this->formData = [['key' => '', 'value' => '']];
        }

        $this->selectedCollectionId = $request->collection_id;
        $this->collectionName = $request->collection?->name ?? '';
        $this->refreshCollectionRequests();

        // Load scripts into UI format
        $this->preRequestScripts = array_map(fn ($s) => [
            'request_id' => $s['request_id'] ?? '',
        ], $request->getPreRequestScripts());

        $this->postResponseScripts = array_map(function ($s) {
            $source = $s['source'] ?? '';
            if ($source === 'status') {
                $sourceType = 'status';
                $sourcePath = '';
            } elseif (str_starts_with($source, 'header.')) {
                $sourceType = 'header';
                $sourcePath = substr($source, 7);
            } else {
                $sourceType = 'body';
                $sourcePath = str_starts_with($source, 'body.') ? substr($source, 5) : $source;
            }

            return [
                'source_type' => $sourceType,
                'source_path' => $sourcePath,
                'target' => $s['target'] ?? '',
            ];
        }, $request->getPostResponseScripts());

        // Convert arrays to key-value pairs (handles both flat and structured formats)
        $this->headers = empty($request->headers)
            ? [['key' => '', 'value' => '']]
            : $this->normalizeKeyValuePairs($request->headers);

        $this->queryParams = empty($request->query_params)
            ? [['key' => '', 'value' => '']]
            : $this->normalizeKeyValuePairs($request->query_params);

        // Load auth settings
        $auth = $request->auth ?? [];
        $this->authType = $auth['type'] ?? 'none';
        $this->authToken = $auth['token'] ?? '';
        $this->authUsername = $auth['username'] ?? '';
        $this->authPassword = $auth['password'] ?? '';
        $this->apiKeyName = $auth['api_key_name'] ?? '';
        $this->apiKeyValue = $auth['api_key_value'] ?? '';

        $this->resetResponse();
    }

    /**
     * Normalize key-value pairs from either flat ({'key': 'value'}) or structured ([{key, value, enabled}]) format.
     *
     * @return array<array{key: string, value: string}>
     */
    private function normalizeKeyValuePairs(array $data): array
    {
        // Structured format: [['key' => '...', 'value' => '...', ...], ...]
        if (isset($data[0]) && is_array($data[0]) && array_key_exists('key', $data[0])) {
            return array_map(fn (array $item) => [
                'key' => is_string($item['key'] ?? '') ? ($item['key'] ?? '') : json_encode($item['key']),
                'value' => is_string($item['value'] ?? '') ? ($item['value'] ?? '') : json_encode($item['value']),
            ], $data);
        }

        // Flat associative format: ['key' => 'value', ...]
        return array_map(fn ($k, $v) => [
            'key' => (string) $k,
            'value' => is_string($v) ? $v : json_encode($v),
        ], array_keys($data), $data);
    }

    #[Renderless]
    public function addHeader(): void
    {
        $this->headers[] = ['key' => '', 'value' => ''];
    }

    #[Renderless]
    public function removeHeader(int $index): void
    {
        unset($this->headers[$index]);
        $this->headers = array_values($this->headers);

        if (empty($this->headers)) {
            $this->headers = [['key' => '', 'value' => '']];
        }
    }

    #[Renderless]
    public function addQueryParam(): void
    {
        $this->queryParams[] = ['key' => '', 'value' => ''];
    }

    #[Renderless]
    public function removeQueryParam(int $index): void
    {
        unset($this->queryParams[$index]);
        $this->queryParams = array_values($this->queryParams);

        if (empty($this->queryParams)) {
            $this->queryParams = [['key' => '', 'value' => '']];
        }
    }

    #[Renderless]
    public function addFormDataField(): void
    {
        $this->formData[] = ['key' => '', 'value' => ''];
    }

    #[Renderless]
    public function removeFormDataField(int $index): void
    {
        unset($this->formData[$index]);
        $this->formData = array_values($this->formData);

        if (empty($this->formData)) {
            $this->formData = [['key' => '', 'value' => '']];
        }
    }

    public function formatJsonBody(): void
    {
        if (empty($this->body)) {
            return;
        }

        $decoded = json_decode($this->body);
        if (json_last_error() === JSON_ERROR_NONE) {
            $this->body = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }

    public function prepareRequest(): ?array
    {
        $this->resetResponse();

        try {
            // Initialize variable substitution service
            $substitutionService = app(VariableSubstitutionService::class);
            $collectionId = $this->selectedCollectionId;

            // Pre-request scripts
            if ($this->requestId) {
                $request = Request::find($this->requestId);
                if ($request?->hasScripts() && ! empty($request->getPreRequestScripts())) {
                    $scriptService = app(ScriptExecutionService::class);
                    $scriptService->executePreRequestScripts($request, $substitutionService);
                }
            }

            // Substitute variables in URL
            $url = $substitutionService->substitute($this->url, $collectionId);

            // Build headers array with variable substitution
            $headers = [];
            foreach ($this->headers as $header) {
                if (! empty($header['key'])) {
                    $key = $substitutionService->substitute($header['key'], $collectionId);
                    $value = $substitutionService->substitute($header['value'], $collectionId);
                    $headers[$key] = $value;
                }
            }

            // Build query params array with variable substitution
            $queryParams = [];
            foreach ($this->queryParams as $param) {
                if (! empty($param['key'])) {
                    $key = $substitutionService->substitute($param['key'], $collectionId);
                    $value = $substitutionService->substitute($param['value'], $collectionId);
                    $queryParams[$key] = $value;
                }
            }

            // Add auth headers with variable substitution
            switch ($this->authType) {
                case 'bearer':
                    if (! empty($this->authToken)) {
                        $token = $substitutionService->substitute($this->authToken, $collectionId);
                        $headers['Authorization'] = 'Bearer '.$token;
                    }
                    break;
                case 'basic':
                    if (! empty($this->authUsername)) {
                        $username = $substitutionService->substitute($this->authUsername, $collectionId);
                        $password = $substitutionService->substitute($this->authPassword, $collectionId);
                        $headers['Authorization'] = 'Basic '.base64_encode($username.':'.$password);
                    }
                    break;
                case 'api-key':
                    if (! empty($this->apiKeyName) && ! empty($this->apiKeyValue)) {
                        $keyName = $substitutionService->substitute($this->apiKeyName, $collectionId);
                        $keyValue = $substitutionService->substitute($this->apiKeyValue, $collectionId);
                        $headers[$keyName] = $keyValue;
                    }
                    break;
            }

            // Build request body based on type with variable substitution
            $formBodyData = collect($this->formData)
                ->filter(fn ($f) => ! empty($f['key']))
                ->mapWithKeys(function ($f) use ($substitutionService, $collectionId) {
                    $key = $substitutionService->substitute($f['key'], $collectionId);
                    $value = $substitutionService->substitute($f['value'], $collectionId);

                    return [$key => $value];
                })
                ->toArray();

            $bodyWithVars = $substitutionService->substitute($this->body, $collectionId);

            $requestBody = match ($this->bodyType) {
                'json' => json_decode($bodyWithVars, true) ?? [],
                'form-data', 'urlencoded' => $formBodyData,
                'raw' => $bodyWithVars,
                default => null,
            };

            // Build Guzzle-compatible options
            $options = [];

            if (! empty($queryParams)) {
                $options['query'] = $queryParams;
            }

            if ($requestBody !== null) {
                $bodyFormatKey = $this->bodyType === 'urlencoded' ? 'form_params' : 'json';
                $options[$bodyFormatKey] = $requestBody;
            }

            return [
                'method' => strtoupper($this->method),
                'url' => $url,
                'headers' => $headers,
                'timeout' => (int) get_setting('requests.timeout', 30),
                'options' => $options,
            ];
        } catch (\Exception $e) {
            $this->error = $e->getMessage();

            return null;
        }
    }

    public function processResponse(int $statusCode, string $body, array $headers, int $duration): void
    {
        $this->statusCode = $statusCode;
        $this->response = $body;
        $this->responseHeaders = $headers;
        $this->duration = $duration;
        $this->isLoading = false;

        // Save to history if request is saved
        if ($this->requestId) {
            RequestHistory::create([
                'request_id' => $this->requestId,
                'method' => $this->method,
                'url' => $this->url,
                'status_code' => $this->statusCode,
                'response_body' => $this->response,
                'response_headers' => $this->responseHeaders,
                'duration_ms' => $this->duration,
                'executed_at' => now(),
            ]);

            $this->dispatch('request-executed');

            // Post-response scripts
            $request = Request::find($this->requestId);
            if (! empty($request) && ! empty($request->getPostResponseScripts())) {
                try {
                    $scriptService = app(ScriptExecutionService::class);
                    $scriptService->executePostResponseScripts($request, $this->statusCode, $this->response, $this->responseHeaders);
                } catch (\Exception) {
                    // Non-blocking: post-response script errors don't fail the request
                }
            }
        }
    }

    public function setRequestError(string $message): void
    {
        $this->error = $message;
        $this->isLoading = false;
    }

    public function saveRequest(): void
    {
        if (! $this->selectedCollectionId) {
            $this->error = 'Please select a collection first';

            return;
        }

        // Convert headers to associative array
        $headersArray = [];
        foreach ($this->headers as $header) {
            if (! empty($header['key'])) {
                $headersArray[$header['key']] = $header['value'];
            }
        }

        // Convert query params to associative array
        $queryParamsArray = [];
        foreach ($this->queryParams as $param) {
            if (! empty($param['key'])) {
                $queryParamsArray[$param['key']] = $param['value'];
            }
        }

        $bodyToSave = $this->body;
        if (in_array($this->bodyType, ['form-data', 'urlencoded'])) {
            $bodyToSave = json_encode($this->formData);
        }

        $scriptsToSave = $this->buildScriptsForStorage();
        $authToSave = $this->buildAuthForStorage();

        if ($this->requestId) {
            // Update existing request
            Request::where('id', $this->requestId)->update([
                'collection_id' => $this->selectedCollectionId,
                'name' => $this->name,
                'url' => $this->url,
                'method' => $this->method,
                'headers' => $headersArray,
                'query_params' => $queryParamsArray,
                'body' => $bodyToSave,
                'body_type' => $this->bodyType,
                'scripts' => $scriptsToSave,
                'auth' => $authToSave,
            ]);
        } else {
            // Create new request
            $request = Request::create([
                'collection_id' => $this->selectedCollectionId,
                'name' => $this->name ?: 'New Request',
                'url' => $this->url,
                'method' => $this->method,
                'headers' => $headersArray,
                'query_params' => $queryParamsArray,
                'body' => $bodyToSave,
                'body_type' => $this->bodyType,
                'scripts' => $scriptsToSave,
                'auth' => $authToSave,
                'order' => Request::where('collection_id', $this->selectedCollectionId)->max('order') + 1,
            ]);

            $this->requestId = $request->id;
        }

        // Mark collection as dirty for remote sync (pass request for granular push)
        if ($this->selectedCollectionId) {
            $collection = Collection::find($this->selectedCollectionId);
            $savedRequest = $this->requestId ? Request::find($this->requestId) : null;

            if ($collection?->sync_enabled && $savedRequest) {
                $findings = (new SensitiveDataScanner)->scanRequest($savedRequest);

                if (! empty($findings)) {
                    $this->pendingSyncRequestId = $savedRequest->id;
                    $this->pendingSyncFindings = $findings;
                    $this->showSyncSensitiveModal = true;
                    // Don't call markDirty yet — wait for user choice
                } else {
                    $collection->markDirty($savedRequest);
                }
            } elseif ($collection) {
                $collection->markDirty($savedRequest);
            }
        }

        $this->dispatch('request-saved');
        $this->dispatch('tab-name-updated',
            requestId: $this->requestId,
            name: $this->name,
            method: $this->method
        );
    }

    public function pushRequest(): void
    {
        $this->saveRequest();

        if (! $this->selectedCollectionId) {
            return;
        }

        // Delegate to sidebar which owns the conflict modal
        $this->dispatch('push-collection', collectionId: $this->selectedCollectionId);
    }

    public function pullRequest(): void
    {
        if (! $this->requestId || ! $this->selectedCollectionId) {
            $this->error = 'Save the request first before pulling';

            return;
        }

        // Delegate to sidebar which owns the conflict modal
        $this->dispatch('pull-collection', collectionId: $this->selectedCollectionId);
    }

    #[On('collections-updated')]
    public function onCollectionsUpdated(): void
    {
        // Reload the current request if it exists (may have been updated by a pull)
        if ($this->requestId) {
            $this->loadRequest($this->requestId);
        }
    }

    public function openCodeModal(): void
    {
        $this->showCodeModal = true;
    }

    public function getGeneratedCode(): string
    {
        $service = new \App\Services\CodeGeneratorService(
            app(VariableSubstitutionService::class),
            $this->selectedCollectionId,
        );

        return $service->generate($this->codeLanguage, [
            'method' => $this->method,
            'url' => $this->url,
            'headers' => $this->headers,
            'queryParams' => $this->queryParams,
            'body' => $this->body,
            'bodyType' => $this->bodyType,
            'formData' => $this->formData,
            'authType' => $this->authType,
            'authToken' => $this->authToken,
            'authUsername' => $this->authUsername,
            'authPassword' => $this->authPassword,
            'apiKeyName' => $this->apiKeyName,
            'apiKeyValue' => $this->apiKeyValue,
        ]);
    }

    #[Renderless]
    public function addPreRequestScript(): void
    {
        $this->preRequestScripts[] = ['request_id' => ''];
    }

    #[Renderless]
    public function removePreRequestScript(int $index): void
    {
        unset($this->preRequestScripts[$index]);
        $this->preRequestScripts = array_values($this->preRequestScripts);
    }

    #[Renderless]
    public function addPostResponseScript(): void
    {
        $this->postResponseScripts[] = ['source_type' => 'body', 'source_path' => '', 'target' => ''];
    }

    #[Renderless]
    public function removePostResponseScript(int $index): void
    {
        unset($this->postResponseScripts[$index]);
        $this->postResponseScripts = array_values($this->postResponseScripts);
    }

    public function refreshCollectionRequests(): void
    {
        if (! $this->selectedCollectionId) {
            $this->collectionRequests = [];

            return;
        }

        $this->collectionRequests = Request::where('collection_id', $this->selectedCollectionId)
            ->where('id', '!=', $this->requestId)
            ->orderBy('name')
            ->get(['id', 'name', 'method'])
            ->toArray();
    }

    private function buildScriptsForStorage(): ?array
    {
        $preRequest = collect($this->preRequestScripts)
            ->filter(fn ($s) => ! empty($s['request_id']))
            ->map(fn ($s) => ['action' => 'send_request', 'request_id' => $s['request_id']])
            ->values()
            ->toArray();

        $postResponse = collect($this->postResponseScripts)
            ->filter(fn ($s) => ! empty($s['target']))
            ->map(function ($s) {
                $source = match ($s['source_type'] ?? 'body') {
                    'status' => 'status',
                    'header' => 'header.'.($s['source_path'] ?? ''),
                    default => 'body.'.($s['source_path'] ?? ''),
                };

                return ['action' => 'set_variable', 'source' => $source, 'target' => $s['target'], 'scope' => 'collection'];
            })
            ->values()
            ->toArray();

        if (empty($preRequest) && empty($postResponse)) {
            return null;
        }

        return array_filter([
            'pre_request' => $preRequest ?: null,
            'post_response' => $postResponse ?: null,
        ]);
    }

    private function buildAuthForStorage(): ?array
    {
        if ($this->authType === 'none') {
            return null;
        }

        $auth = ['type' => $this->authType];

        return match ($this->authType) {
            'bearer' => $auth + ['token' => $this->authToken],
            'basic' => $auth + ['username' => $this->authUsername, 'password' => $this->authPassword],
            'api-key' => $auth + ['api_key_name' => $this->apiKeyName, 'api_key_value' => $this->apiKeyValue],
            default => null,
        };
    }

    public function confirmSyncAsIs(): void
    {
        $request = $this->pendingSyncRequestId ? Request::find($this->pendingSyncRequestId) : null;
        $collection = $this->selectedCollectionId ? Collection::find($this->selectedCollectionId) : null;

        $this->closeSyncSensitiveModal();

        if ($collection && $request) {
            $collection->markDirty($request);
        }
    }

    public function confirmSyncWithoutValues(): void
    {
        $request = $this->pendingSyncRequestId ? Request::find($this->pendingSyncRequestId) : null;
        $collection = $this->selectedCollectionId ? Collection::find($this->selectedCollectionId) : null;

        $this->closeSyncSensitiveModal();

        if ($collection && $request) {
            $collection->markDirty($request, sanitize: true);
        }
    }

    public function skipSync(): void
    {
        $this->closeSyncSensitiveModal();
    }

    public function closeSyncSensitiveModal(): void
    {
        $this->showSyncSensitiveModal = false;
        $this->pendingSyncRequestId = null;
        $this->pendingSyncFindings = [];
    }

    public function resetResponse(): void
    {
        $this->response = null;
        $this->statusCode = null;
        $this->duration = null;
        $this->responseHeaders = [];
        $this->error = null;
    }
};
