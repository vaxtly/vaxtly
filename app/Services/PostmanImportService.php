<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Environment;
use App\Models\Folder;
use App\Models\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class PostmanImportService
{
    /**
     * @var array{collections: int, requests: int, folders: int, environments: int, errors: array<string>}
     */
    private array $result = [
        'collections' => 0,
        'requests' => 0,
        'folders' => 0,
        'environments' => 0,
        'errors' => [],
    ];

    /**
     * Import from a Postman export file (JSON or ZIP).
     *
     * @return array{collections: int, requests: int, folders: int, environments: int, errors: array<string>}
     */
    public function import(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'json') {
            $this->processJsonFile($file->getPathname());
        } elseif ($extension === 'zip') {
            $this->processZipArchive($file->getPathname());
        } else {
            $this->result['errors'][] = "Unsupported file type: {$extension}";
        }

        return $this->result;
    }

    private function processJsonFile(string $path): void
    {
        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->result['errors'][] = 'Invalid JSON file: '.json_last_error_msg();

            return;
        }

        $this->processJson($data);
    }

    private function processJson(array $data): void
    {
        // Detect format type
        if (isset($data['version']) && isset($data['collections']) && is_array($data['collections'])) {
            // Postman workspace dump format (Data Dump export)
            $this->parseDumpFormat($data);
        } elseif (isset($data['info']['_postman_id']) || isset($data['info']['schema'])) {
            $this->parseCollection($data);
        } elseif (isset($data['_postman_variable_scope']) && $data['_postman_variable_scope'] === 'environment') {
            $this->parseEnvironment($data);
        } elseif (isset($data['values']) && isset($data['name'])) {
            // Alternative environment format
            $this->parseEnvironment($data);
        } else {
            $this->result['errors'][] = 'Unknown Postman format';
        }
    }

    private function parseDumpFormat(array $data): void
    {
        foreach ($data['collections'] ?? [] as $collectionData) {
            $this->parseDumpCollection($collectionData);
        }

        foreach ($data['environments'] ?? [] as $environmentData) {
            $this->parseEnvironment($environmentData);
        }
    }

    private function parseDumpCollection(array $data): void
    {
        $name = $data['name'] ?? 'Imported Collection';
        $description = $data['description'] ?? null;
        $variables = $this->mapVariables($data['variables'] ?? []);

        DB::beginTransaction();

        try {
            $collection = Collection::create([
                'name' => $this->generateUniqueCollectionName($name),
                'description' => $description,
                'variables' => $variables,
                'order' => Collection::max('order') + 1,
                'workspace_id' => app(WorkspaceService::class)->activeId(),
            ]);

            $this->result['collections']++;

            // Build folder ID -> Folder model map, respecting parent relationships
            $folderMap = $this->createDumpFolders($data['folders'] ?? [], $collection);

            // Create requests, assigning them to the correct folder
            $this->createDumpRequests($data['requests'] ?? [], $collection, $folderMap);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->result['errors'][] = "Failed to import collection '{$name}': ".$e->getMessage();
        }
    }

    /**
     * Create folders from the flat dump array, respecting parent relationships.
     *
     * @return array<string, Folder> Map of Postman folder ID to created Folder model
     */
    private function createDumpFolders(array $folders, Collection $collection): array
    {
        $folderMap = [];
        $order = 0;

        // Sort so root folders (folder=null) come first, then children
        $rootFolders = array_filter($folders, fn (array $f) => empty($f['folder']));
        $childFolders = array_filter($folders, fn (array $f) => ! empty($f['folder']));

        foreach ($rootFolders as $folderData) {
            $order++;
            $folder = Folder::create([
                'collection_id' => $collection->id,
                'parent_id' => null,
                'name' => $folderData['name'] ?? 'Unnamed Folder',
                'order' => $order,
            ]);
            $folderMap[$folderData['id']] = $folder;
            $this->result['folders']++;
        }

        // Process child folders (may need multiple passes for deep nesting)
        $remaining = $childFolders;
        $maxPasses = 10;

        while (! empty($remaining) && $maxPasses-- > 0) {
            $unresolved = [];
            foreach ($remaining as $folderData) {
                $parentId = $folderData['folder'];
                if (isset($folderMap[$parentId])) {
                    $order++;
                    $folder = Folder::create([
                        'collection_id' => $collection->id,
                        'parent_id' => $folderMap[$parentId]->id,
                        'name' => $folderData['name'] ?? 'Unnamed Folder',
                        'order' => $order,
                    ]);
                    $folderMap[$folderData['id']] = $folder;
                    $this->result['folders']++;
                } else {
                    $unresolved[] = $folderData;
                }
            }
            $remaining = $unresolved;
        }

        return $folderMap;
    }

    /**
     * Create requests from the flat dump array.
     *
     * @param  array<string, Folder>  $folderMap
     */
    private function createDumpRequests(array $requests, Collection $collection, array $folderMap): void
    {
        $order = 0;

        foreach ($requests as $requestData) {
            $order++;
            $folderId = $requestData['folder'] ?? null;
            $folder = $folderId ? ($folderMap[$folderId] ?? null) : null;

            $method = strtoupper($requestData['method'] ?? 'GET');
            $url = $this->stringify($requestData['url'] ?? '');
            $headers = $this->extractDumpHeaders($requestData);
            $queryParams = $this->extractDumpQueryParams($requestData);
            $body = $this->extractDumpBody($requestData);
            $bodyType = $this->mapDumpBodyType($requestData['dataMode'] ?? null);

            Request::create([
                'collection_id' => $collection->id,
                'folder_id' => $folder?->id,
                'name' => $requestData['name'] ?? 'Unnamed Request',
                'url' => $url,
                'method' => $method,
                'headers' => $headers,
                'query_params' => $queryParams,
                'body' => $body,
                'body_type' => $bodyType,
                'order' => $order,
            ]);

            $this->result['requests']++;
        }
    }

    /**
     * Extract headers from a dump-format request.
     *
     * @return array<array{key: string, value: string, enabled: bool}>
     */
    private function extractDumpHeaders(array $requestData): array
    {
        // Prefer structured headerData array
        if (! empty($requestData['headerData'])) {
            $headers = [];
            foreach ($requestData['headerData'] as $header) {
                if (empty($header['key'])) {
                    continue;
                }
                $headers[] = [
                    'key' => $this->stringify($header['key']),
                    'value' => $this->stringify($header['value'] ?? ''),
                    'enabled' => ! ($header['disabled'] ?? false),
                ];
            }

            if (! empty($headers)) {
                return $headers;
            }
        }

        // Fallback: parse the "headers" string (newline-separated "Key: Value" pairs)
        if (! empty($requestData['headers']) && is_string($requestData['headers'])) {
            $headers = [];
            $lines = array_filter(explode("\n", trim($requestData['headers'])));
            foreach ($lines as $line) {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $headers[] = [
                        'key' => trim($parts[0]),
                        'value' => trim($parts[1]),
                        'enabled' => true,
                    ];
                }
            }

            return $headers;
        }

        return [];
    }

    /**
     * Extract query params from a dump-format request.
     *
     * @return array<array{key: string, value: string, enabled: bool}>
     */
    private function extractDumpQueryParams(array $requestData): array
    {
        $params = [];

        foreach ($requestData['queryParams'] ?? [] as $param) {
            if (empty($param['key'])) {
                continue;
            }
            $params[] = [
                'key' => $this->stringify($param['key']),
                'value' => $this->stringify($param['value'] ?? ''),
                'enabled' => ! ($param['disabled'] ?? false),
            ];
        }

        return $params;
    }

    private function extractDumpBody(array $requestData): ?string
    {
        $mode = $requestData['dataMode'] ?? null;

        if ($mode === 'raw') {
            $raw = $requestData['rawModeData'] ?? null;

            return $raw !== null ? $this->stringify($raw) : null;
        }

        if ($mode === 'params' && ! empty($requestData['data'])) {
            $data = [];
            foreach ($requestData['data'] as $item) {
                if (($item['type'] ?? 'text') === 'text') {
                    $data[$this->stringify($item['key'] ?? '')] = $this->stringify($item['value'] ?? '');
                }
            }

            return ! empty($data) ? json_encode($data) : null;
        }

        if ($mode === 'urlencoded' && ! empty($requestData['data'])) {
            $data = [];
            foreach ($requestData['data'] as $item) {
                $data[$this->stringify($item['key'] ?? '')] = $this->stringify($item['value'] ?? '');
            }

            return http_build_query($data);
        }

        return null;
    }

    private function mapDumpBodyType(?string $dataMode): string
    {
        return match ($dataMode) {
            'raw' => 'json',
            'params' => 'form-data',
            'urlencoded' => 'form-urlencoded',
            default => 'none',
        };
    }

    private function processZipArchive(string $path): void
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            $this->result['errors'][] = 'Could not open ZIP archive';

            return;
        }

        $tempDir = sys_get_temp_dir().'/postman_import_'.uniqid();
        mkdir($tempDir, 0777, true);

        $zip->extractTo($tempDir);
        $zip->close();

        // Process all JSON files in the archive
        $this->processDirectory($tempDir);

        // Cleanup
        $this->removeDirectory($tempDir);
    }

    private function processDirectory(string $dir): void
    {
        $files = glob($dir.'/*.json');

        foreach ($files as $file) {
            $this->processJsonFile($file);
        }

        // Check subdirectories
        $subdirs = glob($dir.'/*', GLOB_ONLYDIR);
        foreach ($subdirs as $subdir) {
            $this->processDirectory($subdir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    private function parseCollection(array $data): void
    {
        $info = $data['info'] ?? [];
        $name = $info['name'] ?? 'Imported Collection';
        $description = $info['description'] ?? null;
        $variables = $this->mapVariables($data['variable'] ?? []);

        DB::beginTransaction();

        try {
            $collection = Collection::create([
                'name' => $this->generateUniqueCollectionName($name),
                'description' => $description,
                'variables' => $variables,
                'order' => Collection::max('order') + 1,
                'workspace_id' => app(WorkspaceService::class)->activeId(),
            ]);

            $this->result['collections']++;

            // Parse items (folders and requests)
            $this->parseItems($data['item'] ?? [], $collection, null);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->result['errors'][] = "Failed to import collection '{$name}': ".$e->getMessage();
        }
    }

    /**
     * Recursively parse items, creating folders for item-groups and requests for items.
     */
    private function parseItems(array $items, Collection $collection, ?Folder $parentFolder): void
    {
        $order = 0;

        foreach ($items as $item) {
            $order++;

            // Check if this is a folder (has nested items) or a request
            if (isset($item['item']) && is_array($item['item'])) {
                // This is a folder
                $folder = Folder::create([
                    'collection_id' => $collection->id,
                    'parent_id' => $parentFolder?->id,
                    'name' => $item['name'] ?? 'Unnamed Folder',
                    'order' => $order,
                ]);

                $this->result['folders']++;

                // Recursively process nested items
                $this->parseItems($item['item'], $collection, $folder);
            } elseif (isset($item['request'])) {
                // This is a request
                $this->createRequest($item, $collection, $parentFolder, $order);
            }
        }
    }

    private function createRequest(array $item, Collection $collection, ?Folder $folder, int $order): void
    {
        $requestData = $item['request'];

        // Handle request being a string (simple URL)
        if (is_string($requestData)) {
            $requestData = [
                'method' => 'GET',
                'url' => $requestData,
            ];
        }

        $method = strtoupper($requestData['method'] ?? 'GET');
        $url = $this->extractUrl($requestData['url'] ?? '');
        $queryParams = $this->extractQueryParams($requestData['url'] ?? '');
        $headers = $this->extractHeaders($requestData['header'] ?? []);
        $body = $this->extractBody($requestData['body'] ?? null);
        $bodyType = $this->mapBodyType($requestData['body']['mode'] ?? 'none');

        Request::create([
            'collection_id' => $collection->id,
            'folder_id' => $folder?->id,
            'name' => $item['name'] ?? 'Unnamed Request',
            'url' => $url,
            'method' => $method,
            'headers' => $headers,
            'query_params' => $queryParams,
            'body' => $body,
            'body_type' => $bodyType,
            'order' => $order,
        ]);

        $this->result['requests']++;
    }

    private function parseEnvironment(array $data): void
    {
        $name = $data['name'] ?? 'Imported Environment';
        $values = $data['values'] ?? [];

        $variables = [];
        foreach ($values as $value) {
            $variables[] = [
                'key' => $this->stringify($value['key'] ?? ''),
                'value' => $this->stringify($value['value'] ?? ''),
                'enabled' => $value['enabled'] ?? true,
            ];
        }

        try {
            Environment::create([
                'name' => $this->generateUniqueEnvironmentName($name),
                'variables' => $variables,
                'is_active' => false,
                'order' => Environment::max('order') + 1,
                'workspace_id' => app(WorkspaceService::class)->activeId(),
            ]);

            $this->result['environments']++;
        } catch (\Exception $e) {
            $this->result['errors'][] = "Failed to import environment '{$name}': ".$e->getMessage();
        }
    }

    /**
     * @return array<array{key: string, value: string, enabled: bool}>
     */
    private function mapVariables(array $variables): array
    {
        $mapped = [];

        foreach ($variables as $variable) {
            $mapped[] = [
                'key' => $this->stringify($variable['key'] ?? ''),
                'value' => $this->stringify($variable['value'] ?? ''),
                'enabled' => ! ($variable['disabled'] ?? false),
            ];
        }

        return $mapped;
    }

    /**
     * @param  array<mixed>|string  $url
     */
    private function extractUrl(array|string $url): string
    {
        if (is_string($url)) {
            // Remove query string portion
            $parsed = parse_url($url);

            return ($parsed['scheme'] ?? 'https').'://'
                .($parsed['host'] ?? '')
                .($parsed['path'] ?? '');
        }

        // Postman URL object format
        $raw = $url['raw'] ?? '';

        if (! empty($raw)) {
            $parsed = parse_url($raw);

            return ($parsed['scheme'] ?? 'https').'://'
                .($parsed['host'] ?? '')
                .($parsed['path'] ?? '');
        }

        // Build from components
        $protocol = $url['protocol'] ?? 'https';
        $host = is_array($url['host'] ?? []) ? implode('.', $url['host']) : ($url['host'] ?? '');
        $path = is_array($url['path'] ?? []) ? '/'.implode('/', $url['path']) : ($url['path'] ?? '');
        $port = isset($url['port']) ? ":{$url['port']}" : '';

        return "{$protocol}://{$host}{$port}{$path}";
    }

    /**
     * @param  array<mixed>|string  $url
     * @return array<array{key: string, value: string, enabled: bool}>
     */
    private function extractQueryParams(array|string $url): array
    {
        $params = [];

        if (is_string($url)) {
            $parsed = parse_url($url);
            if (isset($parsed['query'])) {
                parse_str($parsed['query'], $queryArray);
                foreach ($queryArray as $key => $value) {
                    $params[] = [
                        'key' => $key,
                        'value' => is_array($value) ? implode(',', $value) : $value,
                        'enabled' => true,
                    ];
                }
            }

            return $params;
        }

        // Postman URL object format
        $query = $url['query'] ?? [];
        foreach ($query as $param) {
            $params[] = [
                'key' => $this->stringify($param['key'] ?? ''),
                'value' => $this->stringify($param['value'] ?? ''),
                'enabled' => ! ($param['disabled'] ?? false),
            ];
        }

        return $params;
    }

    /**
     * @return array<array{key: string, value: string, enabled: bool}>
     */
    private function extractHeaders(array $headers): array
    {
        $mapped = [];

        foreach ($headers as $header) {
            $mapped[] = [
                'key' => $this->stringify($header['key'] ?? ''),
                'value' => $this->stringify($header['value'] ?? ''),
                'enabled' => ! ($header['disabled'] ?? false),
            ];
        }

        return $mapped;
    }

    private function extractBody(?array $body): ?string
    {
        if (! $body) {
            return null;
        }

        $mode = $body['mode'] ?? 'none';

        $raw = $body['raw'] ?? null;

        return match ($mode) {
            'raw' => is_string($raw) ? $raw : ($raw !== null ? json_encode($raw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null),
            'urlencoded' => $this->buildUrlencodedBody($body['urlencoded'] ?? []),
            'formdata' => $this->buildFormdataBody($body['formdata'] ?? []),
            'graphql' => json_encode($body['graphql'] ?? []),
            default => null,
        };
    }

    private function buildUrlencodedBody(array $params): string
    {
        $data = [];
        foreach ($params as $param) {
            if (! ($param['disabled'] ?? false)) {
                $data[$this->stringify($param['key'] ?? '')] = $this->stringify($param['value'] ?? '');
            }
        }

        return http_build_query($data);
    }

    private function buildFormdataBody(array $params): string
    {
        $data = [];
        foreach ($params as $param) {
            if (! ($param['disabled'] ?? false) && ($param['type'] ?? 'text') === 'text') {
                $data[$this->stringify($param['key'] ?? '')] = $this->stringify($param['value'] ?? '');
            }
        }

        return json_encode($data);
    }

    private function mapBodyType(string $mode): string
    {
        return match ($mode) {
            'raw' => 'json',
            'urlencoded' => 'form-urlencoded',
            'formdata' => 'form-data',
            'graphql' => 'graphql',
            default => 'none',
        };
    }

    /**
     * Ensure a value is a string. Arrays/objects are JSON-encoded.
     */
    private function stringify(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_null($value)) {
            return '';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    }

    private function generateUniqueCollectionName(string $baseName): string
    {
        $name = $baseName;
        $counter = 1;

        while (Collection::where('name', $name)->exists()) {
            $counter++;
            $name = "{$baseName} ({$counter})";
        }

        return $name;
    }

    private function generateUniqueEnvironmentName(string $baseName): string
    {
        $name = $baseName;
        $counter = 1;

        while (Environment::where('name', $name)->exists()) {
            $counter++;
            $name = "{$baseName} ({$counter})";
        }

        return $name;
    }
}
