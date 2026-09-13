<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers;

use PaginiumCMS\Tests\Http\TestCase;

final class I18nRuntimeControllerTest extends TestCase
{
    public function testFrontendCatalogReturnsSettingsModule(): void
    {
        $request = $this->createJsonRequest('GET', '/api/i18n/frontend-catalog?locale=sk');
        $response = $this->handleRequest($request);
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']['modules']['settings'] ?? null);
        $this->assertSame('Nastavenia', $data['data']['modules']['settings']['page']['title'] ?? null);
    }

    public function testFrontendCatalogRequiresLocale(): void
    {
        $request = $this->createJsonRequest('GET', '/api/i18n/frontend-catalog');
        $response = $this->handleRequest($request);

        $this->assertSame(400, $response->getStatusCode());
    }
}
