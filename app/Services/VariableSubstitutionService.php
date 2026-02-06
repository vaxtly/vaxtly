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
    public function substitute(string $text, ?string $collectionId = null): string
    {
        if (empty($text)) {
            return $text;
        }

        $variables = $this->getResolvedVariables($collectionId);

        return preg_replace_callback(
            '/\{\{(\w+)\}\}/',
            function ($matches) use ($variables) {
                $variableName = $matches[1];

                return $variables[$variableName] ?? $matches[0];
            },
            $text
        );
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
            } catch (\Exception) {
                // Gracefully handle Vault connectivity failures
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
}
