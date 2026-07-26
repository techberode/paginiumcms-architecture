<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Models;

/**
 * Custom editor block registered by a plugin manifest (Iteration 60).
 */
final class EditorComponentDefinition
{
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly string $pluginId,
        public readonly string $markdownDirective,
        public readonly string $tiptapNodeType
    ) {
    }

    /**
     * @return array{id: string, label: string, pluginId: string, markdownDirective: string, tiptapNodeType: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'pluginId' => $this->pluginId,
            'markdownDirective' => $this->markdownDirective,
            'tiptapNodeType' => $this->tiptapNodeType,
        ];
    }
}
