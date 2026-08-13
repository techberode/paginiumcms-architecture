<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Support\JsonHelper;

/**
 * Seeds bundled shortcode definitions when registry is empty (It.58d).
 */
final class ShortcodeCatalogSeeder
{
    public function __construct(
        private ShortcodeDefinitionManager $manager,
        private ShortcodeRegistry $registry,
        private ContentCacheService $contentCache,
    ) {
    }

    public function seedIfEmpty(): void
    {
        if ($this->registry->all() !== []) {
            $this->seedMissingBundled();

            return;
        }

        foreach ($this->bundledDefinitions() as $name => $json) {
            $this->manager->save($name, $json);
        }

        $this->contentCache->invalidatePage();
    }

    /**
     * Adds bundled definitions that are not yet in the registry (existing installs).
     */
    public function seedMissingBundled(): void
    {
        $added = 0;
        foreach ($this->bundledDefinitions() as $name => $json) {
            if ($this->registry->get($name) !== null) {
                continue;
            }

            $this->manager->save($name, $json);
            $added++;
        }

        if ($added > 0) {
            $this->contentCache->invalidatePage();
        }
    }

    /**
     * @return array<string, string>
     */
    private function bundledDefinitions(): array
    {
        $definitions = [
            'alert-box' => [
                'name' => 'alert-box',
                'version' => 1,
                'attrs' => [
                    'tone' => [
                        'type' => 'enum',
                        'options' => ['info', 'warn', 'success'],
                    ],
                ],
                'expand' => '<div class="pg-alert pg-alert-{{tone}}" role="note"><div class="pg-alert-body">{{content}}</div></div>',
            ],
            'feature-grid' => [
                'name' => 'feature-grid',
                'version' => 1,
                'attrs' => [
                    'columns' => [
                        'type' => 'enum',
                        'options' => ['2', '3'],
                    ],
                ],
                'expand' => '<div class="pg-grid pg-grid-{{columns}}">{{content}}</div>',
            ],
            'feature-card' => [
                'name' => 'feature-card',
                'version' => 1,
                'attrs' => [
                    'title' => ['type' => 'string'],
                ],
                'expand' => '<article class="pg-card"><h3 class="pg-card-title">{{title}}</h3><div class="pg-card-body">{{content}}</div></article>',
            ],
            'landing-hero' => [
                'name' => 'landing-hero',
                'version' => 1,
                'attrs' => [
                    'title' => ['type' => 'string'],
                    'subtitle' => ['type' => 'string'],
                    'cta' => ['type' => 'string'],
                    'href' => ['type' => 'string'],
                ],
                'expand' => '<section class="pg-hero"><div class="pg-hero-inner"><h1 class="pg-hero-title">{{title}}</h1><p class="pg-hero-subtitle">{{subtitle}}</p><a class="pg-btn pg-btn-primary" href="{{href}}">{{cta}}</a></div></section>',
            ],
        ];

        $encoded = [];
        foreach ($definitions as $name => $payload) {
            $encoded[$name] = JsonHelper::encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        return $encoded;
    }
}
