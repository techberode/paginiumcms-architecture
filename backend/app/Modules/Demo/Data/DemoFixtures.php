<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Demo\Data;

/**
 * Izolované MOCK dáta pre Demo modul (Iterácia 13).
 * Nikdy sa nepoužívajú v produkčných routách – len keď DEMO_MODE=true.
 */
final class DemoFixtures
{
    public const ADMIN_EMAIL = 'demo@paginiumcms.com';

    public const ADMIN_PASSWORD = 'Demo123!';

    public const ADMIN_USER_ID = 'demo_admin_user';

    /**
     * @return list<array{id: string, author: string, text: string, date: string, article_slug: string}>
     */
    public static function sampleComments(): array
    {
        return [
            [
                'id' => 'demo_c_1',
                'author' => 'Peter K.',
                'text' => 'Vynikajúce zhrnutie! FlatFile skutočne šetrí obrovské množstvo starostí so servermi.',
                'date' => 'Pred 2 dňami',
                'article_slug' => 'uvod-do-flatfile',
            ],
            [
                'id' => 'demo_c_2',
                'author' => 'Martina V.',
                'text' => 'Moja obľúbená funkcia je okamžitý Git verzionovací workflow. Super článok.',
                'date' => 'Včera',
                'article_slug' => 'git-workflow',
            ],
        ];
    }

    /**
     * @return list<array{id: string, name: string, email: string, subject: string, message: string, created_at: string}>
     */
    public static function sampleContactMessages(): array
    {
        return [
            [
                'id' => 'demo_msg_1',
                'name' => 'Ján Novák',
                'email' => 'jan@example.com',
                'subject' => 'Demo dopyt',
                'message' => 'Toto je ukážková správa z Demo modulu.',
                'created_at' => '2026-07-01T10:00:00+02:00',
            ],
        ];
    }

    /**
     * @return list<array{id: string, email: string, subscribed_at: string}>
     */
    public static function sampleNewsletterSubscribers(): array
    {
        return [
            [
                'id' => 'demo_nl_1',
                'email' => 'subscriber@example.com',
                'subscribed_at' => '2026-07-01T08:00:00+02:00',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function seedFiles(): array
    {
        return [
            'pages/home.md' => <<<'MD'
---
title: Demo domov
slug: home
status: published
template: default
author: Demo Admin
createdAt: 2026-07-01T08:00:00+02:00
updatedAt: 2026-07-01T08:00:00+02:00
---

# Vitajte v Demo režime

Toto je **predvádzacie vozidlo** PaginiumCMS — skúste admin, články aj verejný web. Zmeny sa periodicky resetujú.
MD,
            'pages/about.md' => <<<'MD'
---
title: O demo module
slug: about
status: published
template: default
author: Demo Admin
createdAt: 2026-07-01T09:00:00+02:00
updatedAt: 2026-07-01T09:00:00+02:00
---

PaginiumCMS demo modul slúži na vyskúšanie open-source CMS bez rizika pre produkčné dáta.
MD,
            'articles/uvod-do-flatfile.md' => <<<'MD'
---
title: Úvod do FlatFile
slug: uvod-do-flatfile
status: published
excerpt: Prečo flat-file CMS šetrí infraštruktúru.
author: Demo Admin
published_at: 2026-07-01T09:00:00+02:00
createdAt: 2026-07-01T09:00:00+02:00
updatedAt: 2026-07-01T09:00:00+02:00
---

Flat-file architektúra umožňuje verzovať obsah cez Git bez SQL migrácií.
MD,
            'articles/git-workflow.md' => <<<'MD'
---
title: Git workflow pre obsah
slug: git-workflow
status: published
excerpt: Ako publikovať obsah cez pull requesty.
author: Demo Admin
published_at: 2026-07-02T11:00:00+02:00
createdAt: 2026-07-02T11:00:00+02:00
updatedAt: 2026-07-02T11:00:00+02:00
---

Každá zmena obsahu môže prejsť review rovnako ako kód aplikácie.
MD,
            'data/settings.json' => self::settingsJson(),
            'data/index/content.json' => self::contentIndexJson(),
            'data/navigation.json' => self::navigationJson(),
        ];
    }

    public static function settingsJson(): string
    {
        return json_encode([
            'general' => [
                'siteName' => 'PaginiumCMS Demo',
                'siteDescription' => 'Open-source flat-file CMS — vyskúšajte admin aj verejný web. Zmeny sa resetujú.',
                'allowRegistration' => false,
            ],
            'comments' => [
                'enabled' => true,
                'requireApproval' => false,
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public static function contentIndexJson(): string
    {
        $entries = [
            self::indexEntry('home', 'page', 'Demo domov', 'pages/home.md', '', '2026-07-01T08:00:00+02:00'),
            self::indexEntry('about', 'page', 'O demo module', 'pages/about.md', '', '2026-07-01T09:00:00+02:00'),
            self::indexEntry(
                'uvod-do-flatfile',
                'article',
                'Úvod do FlatFile',
                'articles/uvod-do-flatfile.md',
                'Prečo flat-file CMS šetrí infraštruktúru.',
                '2026-07-01T09:00:00+02:00'
            ),
            self::indexEntry(
                'git-workflow',
                'article',
                'Git workflow pre obsah',
                'articles/git-workflow.md',
                'Ako publikovať obsah cez pull requesty.',
                '2026-07-02T11:00:00+02:00'
            ),
        ];

        return json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public static function navigationJson(): string
    {
        return json_encode([
            ['id' => 'nav_home', 'label' => 'Domov', 'path' => '/', 'order' => 1],
            ['id' => 'nav_about', 'label' => 'O nás', 'path' => '/about', 'order' => 2],
            ['id' => 'nav_blog', 'label' => 'Blog', 'path' => '/blog', 'order' => 3],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public static function adminUserJson(): string
    {
        $now = time();

        return json_encode([
            'id' => self::ADMIN_USER_ID,
            'email' => self::ADMIN_EMAIL,
            'username' => 'demo',
            'passwordHash' => password_hash(self::ADMIN_PASSWORD, PASSWORD_ARGON2ID),
            'roles' => ['SUPER_ADMIN', 'ADMIN'],
            'name' => 'Demo Admin',
            'active' => true,
            'twoFactorEnabled' => false,
            'twoFactorSecret' => null,
            'twoFactorVerifiedAt' => null,
            'createdAt' => $now,
            'updatedAt' => $now,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * @return array{storage_path: string, description: string}
     */
    public static function meta(): array
    {
        return [
            'storage_path' => 'storage/app/demo',
            'description' => 'Izolované demo úložisko – bez zápisu do reálneho obsahu.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function indexEntry(
        string $slug,
        string $type,
        string $title,
        string $path,
        string $excerpt,
        string $timestamp
    ): array {
        return [
            'slug' => $slug,
            'type' => $type,
            'title' => $title,
            'status' => 'published',
            'author' => 'Demo Admin',
            'path' => $path,
            'excerpt' => $excerpt,
            'tags' => [],
            'updatedAt' => $timestamp,
            'createdAt' => $timestamp,
        ];
    }
}
