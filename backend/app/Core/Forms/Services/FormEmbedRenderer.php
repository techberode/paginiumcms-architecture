<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Forms\Services;

/**
 * Emits public form React island markup (It.101c).
 */
final class FormEmbedRenderer
{
    public function __construct(
        private FormDefinitionRepository $forms,
    ) {
    }

    public function render(string $formId): string
    {
        $formId = strtolower(trim($formId));
        if ($formId === '') {
            return '<!-- form-embed: missing id -->';
        }

        $definition = $this->forms->get($formId);
        if ($definition === null || !(bool) ($definition['enabled'] ?? false)) {
            return '<!-- form-embed: unavailable -->';
        }

        $title = htmlspecialchars((string) ($definition['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<section class="pg-island pg-island--form-embed pg-public-form"'
            . ' data-island="form-embed"'
            . ' data-form-id="' . htmlspecialchars($formId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"'
            . ' data-title="' . $title . '"'
            . '></section>';
    }
}
