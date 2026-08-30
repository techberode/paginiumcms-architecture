<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Demo\Data;

use PaginiumCMS\Support\ContentSeedLoader;

/**
 * Isolated demo flat-file seed data (Iteration 13 v3).
 * Used only when DEMO_MODE=true — never in production content tree.
 */
final class DemoFixtures
{
    public const ADMIN_EMAIL = 'demo@paginiumcms.com';

    public const ADMIN_PASSWORD = 'Demo123!';

    public const ADMIN_USER_ID = 'demo_admin_user';

    /**
     * Legacy fixture samples (DemoDataProvider). Prefer real seed files in {@see seedFiles()}.
     *
     * @return list<array<string, mixed>>
     */
    public static function sampleComments(): array
    {
        return [
            [
                'id' => 'demo_c_1',
                'articleSlug' => 'uvod-do-flatfile',
                'author' => 'Peter K.',
                'email' => 'peter@example.com',
                'content' => 'Vynikajúce zhrnutie! FlatFile skutočne šetrí obrovské množstvo starostí so servermi.',
                'status' => 'approved',
                'createdAt' => '2026-07-01T10:00:00+02:00',
                'approvedAt' => '2026-07-01T10:05:00+02:00',
                'isRead' => true,
                'isArchived' => false,
            ],
            [
                'id' => 'demo_c_2',
                'articleSlug' => 'git-workflow',
                'author' => 'Martina V.',
                'email' => 'martina@example.com',
                'content' => 'Moja obľúbená funkcia je okamžitý Git verzionovací workflow. Super článok.',
                'status' => 'approved',
                'createdAt' => '2026-07-02T14:00:00+02:00',
                'approvedAt' => '2026-07-02T14:10:00+02:00',
                'isRead' => false,
                'isArchived' => false,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function sampleContactMessages(): array
    {
        return [
            [
                'id' => 'demo_msg_1',
                'name' => 'Ján Novák',
                'email' => 'jan@example.com',
                'subject' => 'Demo dopyt',
                'message' => 'Toto je ukážková správa z demo kontaktného formulára.',
                'createdAt' => '2026-07-01T10:00:00+02:00',
                'isRead' => false,
                'isProcessed' => false,
                'isArchived' => false,
                'priority' => 'normal',
                'ip' => '127.0.0.1',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function sampleNewsletterSubscribers(): array
    {
        return [
            [
                'id' => 'demo_nl_1',
                'email' => 'subscriber@example.com',
                'subscribedAt' => '2026-07-01T08:00:00+02:00',
                'source' => 'footer',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function seedFiles(): array
    {
        $files = [
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

# Vitajte v PaginiumCMS

Pre **prezentáciu platformy** otvorte [PaginiumCMS landing](/paginium-cms) — hero, stats, tech stack a sekcie postavené zo shortcodes.

Toto je **demo domov** — plnohodnotný CMS na vyskúšanie adminu aj verejného webu.

## Čo vyskúšať

1. **Admin** — stránky, články, médiá, komentáre, správy, newsletter, plánovač
2. **Verejný web** — blog, kontakt, footer newsletter
3. **Reset** — zmeny sa periodicky obnovia (izolované úložisko `storage/app/demo/`)

> Produkčný obsah na `paginiumcms.com` sa **nikdy** neprepíše.
MD,
            'pages/paginium-cms.md' => ContentSeedLoader::load('paginium-cms-landing.sk.md'),
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

Demo modul slúži na bezpečné vyskúšanie open-source CMS. Všetky zápisy idú do izolovaného stromu.
MD,
            'pages/contact.md' => <<<'MD'
---
title: Kontakt
slug: contact
status: published
template: contact
author: Demo Admin
createdAt: 2026-07-01T09:30:00+02:00
updatedAt: 2026-07-01T09:30:00+02:00
---

Otestujte kontaktný formulár — správy uvidíte v admin sekcii **Správy**.
MD,
            'articles/uvod-do-flatfile.md' => <<<'MD'
---
title: Úvod do FlatFile
slug: uvod-do-flatfile
status: published
excerpt: Prečo flat-file CMS šetrí infraštruktúru.
category: tutorials
author: Demo Admin
published_at: 2026-07-01T09:00:00+02:00
createdAt: 2026-07-01T09:00:00+02:00
updatedAt: 2026-07-01T09:00:00+02:00
commentsEnabled: true
---

Flat-file architektúra umožňuje verzovať obsah cez Git bez SQL migrácií. Skúste pridať komentár nižšie.
MD,
            'articles/git-workflow.md' => <<<'MD'
---
title: Git workflow pre obsah
slug: git-workflow
status: published
excerpt: Ako publikovať obsah cez pull requesty.
category: product
author: Demo Admin
published_at: 2026-07-02T11:00:00+02:00
createdAt: 2026-07-02T11:00:00+02:00
updatedAt: 2026-07-02T11:00:00+02:00
commentsEnabled: true
---

Každá zmena obsahu môže prejsť review rovnako ako kód aplikácie.
MD,
            'data/settings.json' => self::settingsJson(),
            'data/index/content.json' => self::contentIndexJson(),
            'data/taxonomy/categories.json' => self::categoriesJson(),
            'data/navigation.json' => self::navigationJson(),
            'data/comments.json' => self::commentsJson(),
            'data/newsletter/subscribers.json' => self::newsletterJson(),
        ];

        foreach (self::messageSeedFiles() as $relativePath => $contents) {
            $files[$relativePath] = $contents;
        }

        return $files;
    }

    public static function commentsJson(): string
    {
        return json_encode(self::sampleComments(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public static function newsletterJson(): string
    {
        return json_encode(self::sampleNewsletterSubscribers(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, string>
     */
    public static function messageSeedFiles(): array
    {
        $files = [];
        foreach (self::sampleContactMessages() as $message) {
            $id = (string) ($message['id'] ?? 'demo_msg');
            $payload = $message;
            $payload['path'] = 'data/messages/' . $id . '.json';
            $files['data/messages/' . $id . '.json'] = json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
        }

        return $files;
    }

    public static function settingsJson(): string
    {
        return json_encode([
            'general' => [
                'siteName' => 'PaginiumCMS Demo',
                'siteDescription' => 'Open-source flat-file CMS — vyskúšajte admin aj verejný web. Zmeny sa resetujú.',
                'language' => 'sk',
                'allowRegistration' => false,
            ],
            'comments' => [
                'enabled' => true,
                'requireApproval' => false,
                'allowGuestComments' => true,
            ],
            'newsletter' => [
                'footerEnabled' => true,
                'footerHint' => 'Prihláste sa na odber — v demo režime ide len o ukážku.',
            ],
            'contact' => [
                'subjects' => "Všeobecný dotaz\nTechnická podpora\nDemo dopyt",
                'allowCustomSubject' => true,
            ],
            'company' => [
                'showOnContactPage' => true,
                'name' => 'PaginiumCMS Demo s.r.o.',
                'email' => 'demo@paginiumcms.com',
                'phone' => '+421 900 000 000',
                'address' => 'Demo ulica 1, Bratislava',
            ],
            'appearance' => [
                'colorScheme' => 'mono-zinc',
                'mode' => 'dark',
                'allowUserToggle' => true,
                'previewTemplate' => 'landing',
            ],
            'content' => [
                'blogSidebarEnabled' => true,
                'blogSidebarPlacement' => 'right',
                'blogSidebarShowTags' => true,
                'blogSidebarShowCategories' => true,
                'blogSidebarShowLatest' => true,
                'blogSidebarShowPopular' => true,
            ],
            'login' => [
                'pageTitle' => 'Demo prihlásenie',
                'pageDescription' => 'Použite demo účet alebo tlačidlo „Vyplniť demo údaje“.',
                'infoBullets' => "Plný admin prístup\nIzolované úložisko\nPeriodický reset",
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public static function contentIndexJson(): string
    {
        $entries = [
            self::indexEntry('home', 'page', 'Demo domov', 'pages/home.md', '', '2026-07-01T08:00:00+02:00'),
            self::indexEntry(
                'paginium-cms',
                'page',
                'PaginiumCMS',
                'pages/paginium-cms.md',
                'Hybrid flat-file CMS — admin SPA, bezpečný API, shortcodes pre marketing bez SQL.',
                '2026-08-17T10:00:00+02:00'
            ),
            self::indexEntry('about', 'page', 'O demo module', 'pages/about.md', '', '2026-07-01T09:00:00+02:00'),
            self::indexEntry('contact', 'page', 'Kontakt', 'pages/contact.md', '', '2026-07-01T09:30:00+02:00'),
            self::indexEntry(
                'uvod-do-flatfile',
                'article',
                'Úvod do FlatFile',
                'articles/uvod-do-flatfile.md',
                'Prečo flat-file CMS šetrí infraštruktúru.',
                '2026-07-01T09:00:00+02:00',
                'tutorials'
            ),
            self::indexEntry(
                'git-workflow',
                'article',
                'Git workflow pre obsah',
                'articles/git-workflow.md',
                'Ako publikovať obsah cez pull requesty.',
                '2026-07-02T11:00:00+02:00',
                'product'
            ),
        ];

        return json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public static function navigationJson(): string
    {
        return json_encode([
            ['id' => 'nav_paginium', 'label' => 'PaginiumCMS', 'path' => '/paginium-cms', 'order' => 1],
            ['id' => 'nav_home', 'label' => 'Domov', 'path' => '/', 'order' => 2],
            ['id' => 'nav_about', 'label' => 'O nás', 'path' => '/about', 'order' => 3],
            ['id' => 'nav_contact', 'label' => 'Kontakt', 'path' => '/contact', 'order' => 4],
            ['id' => 'nav_blog', 'label' => 'Blog', 'path' => '/blog', 'order' => 5],
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

    public static function categoriesJson(): string
    {
        return json_encode([
            'news' => ['slug' => 'news', 'label' => 'News'],
            'security' => ['slug' => 'security', 'label' => 'Security'],
            'tutorials' => ['slug' => 'tutorials', 'label' => 'Tutorials'],
            'product' => ['slug' => 'product', 'label' => 'Product'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
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
        string $timestamp,
        string $category = ''
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
            'category' => $category,
            'updatedAt' => $timestamp,
            'createdAt' => $timestamp,
        ];
    }
}
