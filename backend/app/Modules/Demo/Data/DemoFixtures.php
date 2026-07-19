<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Demo\Data;

/**
 * Izolované MOCK dáta pre Demo modul (Iterácia 13).
 * Nikdy sa nepoužívajú v produkčných routách – len keď DEMO_MODE=true.
 */
final class DemoFixtures
{
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
---

# Vitajte v Demo režime

Tento obsah žije len v `storage/app/demo/` a nemení produkčné súbory.
MD,
            'pages/about.md' => <<<'MD'
---
title: O demo module
slug: about
status: published
template: default
---

PaginiumCMS demo modul slúži na školenia a sandbox testy.
MD,
            'articles/uvod-do-flatfile.md' => <<<'MD'
---
title: Úvod do FlatFile
slug: uvod-do-flatfile
status: published
excerpt: Prečo flat-file CMS šetrí infraštruktúru.
published_at: 2026-07-01T09:00:00+02:00
---

Flat-file architektúra umožňuje verzovať obsah cez Git bez SQL migrácií.
MD,
            'articles/git-workflow.md' => <<<'MD'
---
title: Git workflow pre obsah
slug: git-workflow
status: published
excerpt: Ako publikovať obsah cez pull requesty.
published_at: 2026-07-02T11:00:00+02:00
---

Každá zmena obsahu môže prejsť review rovnako ako kód aplikácie.
MD,
        ];
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
}
