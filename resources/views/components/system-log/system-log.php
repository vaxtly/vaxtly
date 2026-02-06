<?php

use App\Models\RequestHistory;
use App\Services\SessionLogService;
use App\Traits\HttpColorHelper;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Component;

new class extends Component
{
    use HttpColorHelper;

    #[Session]
    public string $activeTab = 'requests';

    #[Session]
    public bool $isExpanded = false;

    public ?string $expandedHistoryId = null;

    public array $expandedHistoryData = [];

    public function selectTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->isExpanded = true;
    }

    public function close(): void
    {
        $this->isExpanded = false;
    }

    #[On('collections-updated')]
    #[On('environments-updated')]
    #[On('request-saved')]
    #[On('request-executed')]
    public function refresh(): void
    {
        // Force re-computation of logs by clearing the cache
        unset($this->requestHistory);
    }

    #[Computed]
    public function requestHistory(): array
    {
        return RequestHistory::query()
            ->with('request:id,name,collection_id', 'request.collection:id,name')
            ->orderByDesc('executed_at')
            ->limit(50)
            ->get()
            ->map(fn (RequestHistory $h) => [
                'id' => $h->id,
                'request_name' => $h->request?->name ?? 'Unknown',
                'collection_name' => $h->request?->collection?->name ?? 'Unknown',
                'method' => $h->method ?? 'GET',
                'url' => $h->url ?? '',
                'status_code' => $h->status_code,
                'duration_ms' => $h->duration_ms,
                'executed_at' => $h->executed_at->diffForHumans(),
                'executed_at_full' => $h->executed_at->format('Y-m-d H:i:s'),
            ])
            ->toArray();
    }

    #[Computed]
    public function systemLogs(): array
    {
        return app(SessionLogService::class)->getSystemLogs();
    }

    public function toggleHistoryEntry(string $historyId): void
    {
        if ($this->expandedHistoryId === $historyId) {
            $this->expandedHistoryId = null;
            $this->expandedHistoryData = [];

            return;
        }

        $this->expandedHistoryId = $historyId;
        $this->expandedHistoryData = $this->getHistoryEntry($historyId);
    }

    public function getHistoryEntry(string $historyId): array
    {
        $entry = RequestHistory::find($historyId);

        if (! $entry) {
            return [];
        }

        $responseBody = $entry->response_body ?? '';
        $decoded = json_decode($responseBody);
        if (json_last_error() === JSON_ERROR_NONE) {
            $responseBody = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return [
            'response_body' => $responseBody,
            'response_body_colorized' => $this->colorizeJson($responseBody),
            'response_headers' => $entry->response_headers ?? [],
        ];
    }

    public function loadHistoryEntry(string $historyId): void
    {
        $entry = RequestHistory::find($historyId);

        if (! $entry) {
            return;
        }

        // Dispatch event to load this entry in the request builder
        $this->dispatch('load-history-entry', historyId: $historyId);
    }

    public function deleteHistoryEntry(string $historyId): void
    {
        RequestHistory::where('id', $historyId)->delete();
        $this->expandedHistoryId = null;
        $this->expandedHistoryData = [];
        unset($this->requestHistory);
    }

    public function clearSystemLogs(): void
    {
        app(SessionLogService::class)->clearLogs();
    }
};
