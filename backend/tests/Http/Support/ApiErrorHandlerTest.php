<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Support;

use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Http\Support\ApiErrorHandler;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Testy jednotného Error Handlera (Iterácia 4).
 * Overuje mapovanie výnimiek na jednotný JSON obal a stavové kódy.
 */
class ApiErrorHandlerTest extends TestCase
{
    private ApiErrorHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new ApiErrorHandler(new ResponseFactory());
    }

    public function testValidationExceptionMapsTo422(): void
    {
        $exception = new ValidationException(['siteName' => ['Pole je povinné.']]);

        $response = ($this->handler)($this->request(), $exception, false, false, false);
        $body = $this->decode($response);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($body['success']);
        $this->assertArrayHasKey('errors', $body);
        $this->assertSame(['Pole je povinné.'], $body['errors']['siteName']);
    }

    public function testHttpExceptionKeepsItsStatus(): void
    {
        $exception = new HttpNotFoundException($this->request());

        $response = ($this->handler)($this->request(), $exception, false, false, false);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertFalse($this->decode($response)['success']);
    }

    public function testGenericExceptionMapsTo500AndHidesDetailsWithoutDebug(): void
    {
        $exception = new RuntimeException('interné tajomstvo');

        $response = ($this->handler)($this->request(), $exception, false, false, false);
        $body = $this->decode($response);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('Vnútorná chyba servera', $body['error']);
        $this->assertArrayNotHasKey('exception', $body);
    }

    public function testGenericExceptionShowsDetailsWithDebug(): void
    {
        $exception = new RuntimeException('interné tajomstvo');

        $response = ($this->handler)($this->request(), $exception, true, false, false);
        $body = $this->decode($response);

        $this->assertSame('interné tajomstvo', $body['error']);
        $this->assertArrayHasKey('exception', $body);
    }

    private function request(): \Psr\Http\Message\ServerRequestInterface
    {
        return (new ServerRequestFactory())->createServerRequest('GET', '/api/admin/settings');
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(\Psr\Http\Message\ResponseInterface $response): array
    {
        $response->getBody()->rewind();
        $decoded = json_decode((string) $response->getBody(), true);
        self::assertIsArray($decoded);

        return $decoded;
    }
}
