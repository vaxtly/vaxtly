<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Environment;

class VariableSubstitutionService
{
    /**
     * Substitute variables in a string using {{variableName}} syntax.
     *
     * @param  string  $text  The text containing variable placeholders
     * @param  string|null  $collectionId  Optional collection ID for collection-level variables
     * @return string The text with variables substituted
     */
    public function substitute(string $text, ?string $collectionId = null, int $maxDepth = 10): string
    {
        if (empty($text)) {
            return $text;
        }

        $variables = $this->getResolvedVariables($collectionId);

        for ($i = 0; $i < $maxDepth; $i++) {
            $result = preg_replace_callback(
                '/\{\{([\w\-\.]+)\}\}/',
                function ($matches) use ($variables) {
                    $variableName = $matches[1];

                    return $variables[$variableName] ?? $matches[0];
                },
                $text
            );

            if ($result === $text) {
                break;
            }

            $text = $result;
        }

        return $text;
    }

    /**
     * Substitute variables in an array of key-value pairs.
     *
     * @param  array<string, string>  $items
     * @return array<string, string>
     */
    public function substituteArray(array $items, ?string $collectionId = null): array
    {
        $result = [];
        foreach ($items as $key => $value) {
            $substitutedKey = $this->substitute($key, $collectionId);
            $substitutedValue = $this->substitute($value, $collectionId);
            $result[$substitutedKey] = $substitutedValue;
        }

        return $result;
    }

    /**
     * Get resolved variables merging active global environment with collection-level overrides.
     *
     * Collection variables take precedence over global environment variables.
     *
     * @param  string|null  $collectionId  Optional collection ID for collection-level variables
     * @return array<string, string> Key-value pairs of variable names and their values
     */
    public function getResolvedVariables(?string $collectionId = null): array
    {
        $variables = [];

        $workspaceId = app(WorkspaceService::class)->activeId();
        $activeEnvironment = Environment::active()->forWorkspace($workspaceId)->first();
        if ($activeEnvironment) {
            try {
                $variables = $activeEnvironment->getEnabledVariables();
            } catch (\Exception $e) {
                report($e);
            }
        }

        if ($collectionId) {
            $collection = Collection::find($collectionId);
            if ($collection) {
                $collectionVariables = $collection->getEnabledVariables();
                $variables = array_merge($variables, $collectionVariables);
            }
        }

        return $variables;
    }

    /**
     * Get resolved variables with their values and source labels.
     *
     * Collection variables override environment variables — the source reflects the winner.
     *
     * @param  string|null  $collectionId  Optional collection ID for collection-level variables
     * @return array<string, array{value: string, source: string}>
     */
    public function getResolvedVariablesWithSource(?string $collectionId = null): array
    {
        $variables = [];

        $workspaceId = app(WorkspaceService::class)->activeId();
        $activeEnvironment = Environment::active()->forWorkspace($workspaceId)->first();
        if ($activeEnvironment) {
            try {
                $envLabel = 'Env: '.$activeEnvironment->name;
                foreach ($activeEnvironment->getEnabledVariables() as $key => $value) {
                    $variables[$key] = ['value' => $value, 'source' => $envLabel];
                }
            } catch (\Exception $e) {
                report($e);
            }
        }

        if ($collectionId) {
            $collection = Collection::find($collectionId);
            if ($collection) {
                foreach ($collection->getEnabledVariables() as $key => $value) {
                    $variables[$key] = ['value' => $value, 'source' => 'Collection'];
                }
            }
        }

        // Resolve nested variable references so tooltip shows final values
        $flatMap = array_map(fn (array $entry) => $entry['value'], $variables);
        foreach ($variables as $key => $entry) {
            $resolved = $entry['value'];
            for ($i = 0; $i < 10; $i++) {
                $result = preg_replace_callback(
                    '/\{\{([\w\-\.]+)\}\}/',
                    fn ($m) => $flatMap[$m[1]] ?? $m[0],
                    $resolved
                );
                if ($result === $resolved) {
                    break;
                }
                $resolved = $result;
            }
            $variables[$key]['value'] = $resolved;
        }

        return $variables;
    }
}
