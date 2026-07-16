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
 * @return array<int|string, mixed>
 */public static function sampleComments(): array
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
 * @return array<int|string, mixed>
 */public static function sampleContactMessages(): array
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
 * @return array<int|string, mixed>
 */public static function sampleNewsletterSubscribers(): array
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
     * @return array{storage_path: string, description: string}
 * @return array<int|string, mixed>
 */public static function meta(): array
    {
        return [
            'storage_path' => 'storage/app/demo',
            'description' => 'Izolované demo úložisko – bez zápisu do reálneho obsahu.',
        ];
    }
}
