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
                'expand' => '<article class="pg-card pg-reveal"><h3 class="pg-card-title">{{title}}</h3><div class="pg-card-body">{{content}}</div></article>',
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
            'cta-banner' => [
                'name' => 'cta-banner',
                'version' => 1,
                'attrs' => [
                    'title' => ['type' => 'string'],
                    'subtitle' => ['type' => 'string'],
                    'cta' => ['type' => 'string'],
                    'href' => ['type' => 'string'],
                    'tone' => [
                        'type' => 'enum',
                        'options' => ['primary', 'muted'],
                    ],
                ],
                'expand' => '<section class="pg-cta pg-cta-{{tone}}"><div class="pg-cta-inner"><h2 class="pg-cta-title">{{title}}</h2><p class="pg-cta-subtitle">{{subtitle}}</p><a class="pg-btn pg-btn-primary pg-cta-link" href="{{href}}">{{cta}}</a></div></section>',
            ],
            'stats-row' => [
                'name' => 'stats-row',
                'version' => 1,
                'attrs' => [],
                'expand' => '<div class="pg-stats pg-reveal">{{content}}</div>',
            ],
            'stat-item' => [
                'name' => 'stat-item',
                'version' => 1,
                'attrs' => [
                    'value' => ['type' => 'string'],
                    'label' => ['type' => 'string'],
                ],
                'expand' => '<div class="pg-stat"><span class="pg-stat-value">{{value}}</span><span class="pg-stat-label">{{label}}</span></div>',
            ],
            'testimonial' => [
                'name' => 'testimonial',
                'version' => 1,
                'attrs' => [
                    'quote' => ['type' => 'string'],
                    'author' => ['type' => 'string'],
                    'role' => ['type' => 'string'],
                ],
                'expand' => '<blockquote class="pg-testimonial"><p class="pg-testimonial-quote">{{quote}}</p><footer class="pg-testimonial-meta"><cite class="pg-testimonial-author">{{author}}</cite><span class="pg-testimonial-role">{{role}}</span></footer></blockquote>',
            ],
            'pricing-table' => [
                'name' => 'pricing-table',
                'version' => 1,
                'attrs' => [
                    'columns' => [
                        'type' => 'enum',
                        'options' => ['2', '3'],
                    ],
                ],
                'expand' => '<div class="pg-pricing pg-pricing-cols-{{columns}}">{{content}}</div>',
            ],
            'pricing-plan' => [
                'name' => 'pricing-plan',
                'version' => 1,
                'attrs' => [
                    'name' => ['type' => 'string'],
                    'price' => ['type' => 'string'],
                    'period' => ['type' => 'string'],
                    'cta' => ['type' => 'string'],
                    'href' => ['type' => 'string'],
                    'variant' => [
                        'type' => 'enum',
                        'options' => ['default', 'featured'],
                    ],
                ],
                'expand' => '<article class="pg-plan pg-plan-{{variant}}"><h3 class="pg-plan-name">{{name}}</h3><p class="pg-plan-price"><span class="pg-plan-amount">{{price}}</span><span class="pg-plan-period">{{period}}</span></p><ul class="pg-plan-list">{{content}}</ul><a class="pg-btn pg-btn-primary pg-plan-cta" href="{{href}}">{{cta}}</a></article>',
            ],
            'pricing-feature' => [
                'name' => 'pricing-feature',
                'version' => 1,
                'attrs' => [
                    'text' => ['type' => 'string'],
                ],
                'expand' => '<li class="pg-plan-feature">{{text}}</li>',
            ],
            'section-head' => [
                'name' => 'section-head',
                'version' => 1,
                'attrs' => [
                    'anchor' => ['type' => 'string'],
                    'eyebrow' => ['type' => 'string'],
                    'title' => ['type' => 'string'],
                    'subtitle' => ['type' => 'string'],
                ],
                'expand' => '<header class="pg-section-head pg-reveal" id="{{anchor}}"><p class="pg-section-eyebrow">{{eyebrow}}</p><h2 class="pg-section-title">{{title}}</h2><p class="pg-section-subtitle">{{subtitle}}</p></header>',
            ],
            'showcase-hero' => [
                'name' => 'showcase-hero',
                'version' => 1,
                'attrs' => [
                    'badge' => ['type' => 'string'],
                    'title' => ['type' => 'string'],
                    'subtitle' => ['type' => 'string'],
                    'terminal' => ['type' => 'string'],
                    'cta' => ['type' => 'string'],
                    'href' => ['type' => 'string'],
                    'cta2' => ['type' => 'string'],
                    'href2' => ['type' => 'string'],
                ],
                'expand' => '<section class="pg-showcase-hero pg-reveal"><div class="pg-showcase-hero-inner"><p class="pg-showcase-badge">{{badge}}</p><h1 class="pg-showcase-title">{{title}}</h1><p class="pg-showcase-subtitle">{{subtitle}}</p><pre class="pg-showcase-terminal" aria-label="Terminal preview"><code>$ {{terminal}}</code></pre><div class="pg-showcase-actions"><a class="pg-btn pg-btn-primary" href="{{href}}">{{cta}}</a><a class="pg-btn pg-btn-ghost" href="{{href2}}">{{cta2}}</a></div></div></section>',
            ],
            'stack-grid' => [
                'name' => 'stack-grid',
                'version' => 1,
                'attrs' => [],
                'expand' => '<div class="pg-stack-grid pg-reveal">{{content}}</div>',
            ],
            'stack-tag' => [
                'name' => 'stack-tag',
                'version' => 1,
                'attrs' => [
                    'label' => ['type' => 'string'],
                ],
                'expand' => '<span class="pg-stack-tag">{{label}}</span>',
            ],
        ];

        $encoded = [];
        foreach ($definitions as $name => $payload) {
            $encoded[$name] = JsonHelper::encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        return $encoded;
    }
}
