<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Services;

use PaginiumCMS\Core\Editor\Models\EditorComponentDefinition;
use PaginiumCMS\Http\Extensions\Contracts\PluginManagerInterface;

/**
 * Aggregates custom editor components from enabled plugin manifests (Iteration 60).
 */
final class EditorComponentRegistry
{
    public function __construct(
        private PluginManagerInterface $plugins
    ) {
    }

    /**
     * @return list<EditorComponentDefinition>
     */
    public function listRegistered(): array
    {
        $components = [];
        foreach ($this->plugins->listEnabledEditorComponents() as $definition) {
            $components[] = $definition;
        }

        usort($components, static fn (EditorComponentDefinition $a, EditorComponentDefinition $b): int => strcmp($a->id, $b->id));

        return $components;
    }

    /**
     * @return list<array{id: string, label: string, pluginId: string, markdownDirective: string, tiptapNodeType: string}>
     */
    public function listRegisteredForApi(): array
    {
        return array_map(
            static fn (EditorComponentDefinition $definition): array => $definition->toArray(),
            $this->listRegistered()
        );
    }

    public function get(string $id): ?EditorComponentDefinition
    {
        foreach ($this->listRegistered() as $definition) {
            if ($definition->id === $id) {
                return $definition;
            }
        }

        return null;
    }

    public function isRegistered(string $id): bool
    {
        return $this->get($id) !== null;
    }

    public function getByTiptapNodeType(string $nodeType): ?EditorComponentDefinition
    {
        foreach ($this->listRegistered() as $definition) {
            if ($definition->tiptapNodeType === $nodeType) {
                return $definition;
            }
        }

        return null;
    }

    public function getByMarkdownDirective(string $directive): ?EditorComponentDefinition
    {
        foreach ($this->listRegistered() as $definition) {
            if ($definition->markdownDirective === $directive) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function registeredTiptapNodeTypes(): array
    {
        return array_values(array_unique(array_map(
            static fn (EditorComponentDefinition $definition): string => $definition->tiptapNodeType,
            $this->listRegistered()
        )));
    }
}
