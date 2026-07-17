<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Support;

use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\PaginationMeta;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Response;

final class JsonResponderTest extends TestCase
{
    private JsonResponder $responder;

    protected function setUp(): void
    {
        $this->responder = new JsonResponder();
    }

    public function testSuccessEnvelope(): void
    {
        $response = $this->responder->success(new Response(), ['id' => 'x'], 200, 'OK');

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->decode($response);
        $this->assertTrue($data['success']);
        $this->assertSame(['id' => 'x'], $data['data']);
        $this->assertSame('OK', $data['message']);
    }

    public function testPaginatedEnvelope(): void
    {
        $meta = new PaginationMeta(1, 10, 25);
        $response = $this->responder->paginated(new Response(), [['slug' => 'a']], $meta);

        $data = $this->decode($response);
        $this->assertTrue($data['success']);
        $this->assertSame(1, $data['meta']['page']);
        $this->assertSame(10, $data['meta']['per_page']);
        $this->assertSame(25, $data['meta']['total']);
        $this->assertSame(3, $data['meta']['total_pages']);
    }

    public function testErrorEnvelope(): void
    {
        $response = $this->responder->error(new Response(), 'Bad request', 400);

        $data = $this->decode($response);
        $this->assertFalse($data['success']);
        $this->assertSame('Bad request', $data['error']);
        $this->assertArrayNotHasKey('errors', $data);
    }

    public function testValidationEnvelope(): void
    {
        $response = $this->responder->validation(
            new Response(),
            'Validation failed',
            ['email' => ['Invalid email']]
        );

        $this->assertSame(422, $response->getStatusCode());
        $data = $this->decode($response);
        $this->assertFalse($data['success']);
        $this->assertSame('Validation failed', $data['error']);
        $this->assertSame(['email' => ['Invalid email']], $data['errors']);
    }

    public function testConflictEnvelope(): void
    {
        $response = $this->responder->conflict(
            new Response(),
            'Conflict',
            ['conflict' => ['serverRevision' => 'abc']]
        );

        $this->assertSame(409, $response->getStatusCode());
        $data = $this->decode($response);
        $this->assertFalse($data['success']);
        $this->assertSame('Conflict', $data['error']);
        $this->assertSame(['serverRevision' => 'abc'], $data['conflict']);
    }

    public function testRespondLegacyEnvelope(): void
    {
        $response = $this->responder->respond(new Response(), [
            'success' => true,
            'user' => ['id' => '1'],
            'requires_two_factor' => true,
        ]);

        $data = $this->decode($response);
        $this->assertTrue($data['success']);
        $this->assertSame(['id' => '1'], $data['user']);
        $this->assertTrue($data['requires_two_factor']);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function decode(\Psr\Http\Message\ResponseInterface $response): array
    {
        $decoded = json_decode((string) $response->getBody(), true);

        return is_array($decoded) ? $decoded : [];
    }
}
