<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Translation;

use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Core\Translation\Exception\TranslationException;
use PaginiumCMS\Core\Translation\Services\TranslationHttpClient;
use PHPUnit\Framework\TestCase;

final class TranslationHttpClientTest extends TestCase
{
    public function testBlocksPrivateSsrfTargetWithoutAllowList(): void
    {
        $client = new TranslationHttpClient(new OutboundUrlGuard(false, false));

        $this->expectException(TranslationException::class);
        $this->expectExceptionMessage('not allowed');
        $client->request('GET', 'https://127.0.0.1/translate', null, 5);
    }
}
