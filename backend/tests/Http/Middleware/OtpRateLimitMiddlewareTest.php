<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Middleware;

use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Cache\Drivers\MemoryDriver;
use PaginiumCMS\Http\Middleware\OtpResendRateLimitMiddleware;
use PaginiumCMS\Http\Middleware\OtpStartRateLimitMiddleware;
use PaginiumCMS\Http\Middleware\OtpVerifyRateLimitMiddleware;
use PaginiumCMS\Support\JsonHelper;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

/**
 * Overuje dedikovaný OTP rate limit (audit S10 / ISS-058).
 */
final class OtpRateLimitMiddlewareTest extends TestCase
{
    /** @var array{env: string|false, server: mixed, srv: mixed} */
    private array $savedEnv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->savedEnv = [
            'env' => getenv('APP_ENV'),
            'server' => $_ENV['APP_ENV'] ?? null,
            'srv' => $_SERVER['APP_ENV'] ?? null,
        ];
        putenv('APP_ENV=production');
        $_ENV['APP_ENV'] = 'production';
        $_SERVER['APP_ENV'] = 'production';
    }

    protected function tearDown(): void
    {
        if ($this->savedEnv['env'] === false) {
            putenv('APP_ENV');
        } else {
            putenv('APP_ENV=' . $this->savedEnv['env']);
        }
        $_ENV['APP_ENV'] = $this->savedEnv['server'];
        $_SERVER['APP_ENV'] = $this->savedEnv['srv'];
        parent::tearDown();
    }

    public function testVerifyReturns429AfterProductionLimit(): void
    {
        $middleware = new OtpVerifyRateLimitMiddleware(new CacheManager(new MemoryDriver()));
        $handler = $this->okHandler();
        $challengeId = 'verify_' . uniqid();

        for ($i = 0; $i < 10; $i++) {
            $response = $middleware->process(
                $this->jsonRequest(['challenge_id' => $challengeId, 'code' => '123456']),
                $handler
            );
            $this->assertSame(200, $response->getStatusCode(), 'Attempt ' . ($i + 1));
        }

        $blocked = $middleware->process(
            $this->jsonRequest(['challenge_id' => $challengeId, 'code' => '123456']),
            $handler
        );

        $this->assertSame(429, $blocked->getStatusCode());
    }

    public function testResendUsesChallengeScopedKey(): void
    {
        $middleware = new OtpResendRateLimitMiddleware(new CacheManager(new MemoryDriver()));
        $handler = $this->okHandler();

        $firstChallenge = 'resend_a_' . uniqid();
        $secondChallenge = 'resend_b_' . uniqid();

        for ($i = 0; $i < 3; $i++) {
            $response = $middleware->process(
                $this->jsonRequest(['challenge_id' => $firstChallenge]),
                $handler
            );
            $this->assertSame(200, $response->getStatusCode(), 'First challenge attempt ' . ($i + 1));
        }

        $blocked = $middleware->process(
            $this->jsonRequest(['challenge_id' => $firstChallenge]),
            $handler
        );
        $this->assertSame(429, $blocked->getStatusCode());

        $otherChallenge = $middleware->process(
            $this->jsonRequest(['challenge_id' => $secondChallenge]),
            $handler
        );
        $this->assertSame(200, $otherChallenge->getStatusCode());
    }

    public function testStartUsesEmailScopedKey(): void
    {
        $middleware = new OtpStartRateLimitMiddleware(new CacheManager(new MemoryDriver()));
        $handler = $this->okHandler();
        $email = 'otp_rate_' . uniqid() . '@example.com';

        for ($i = 0; $i < 5; $i++) {
            $response = $middleware->process(
                $this->jsonRequest([
                    'email' => $email,
                    'password' => 'StrongP@ssw0rd123!',
                    'name' => 'Rate Limit User',
                ]),
                $handler
            );
            $this->assertSame(200, $response->getStatusCode(), 'Start attempt ' . ($i + 1));
        }

        $blocked = $middleware->process(
            $this->jsonRequest([
                'email' => $email,
                'password' => 'StrongP@ssw0rd123!',
                'name' => 'Rate Limit User',
            ]),
            $handler
        );

        $this->assertSame(429, $blocked->getStatusCode());
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonRequest(array $payload): ServerRequestInterface
    {
        $factory = new ServerRequestFactory();
        $streamFactory = new StreamFactory();
        $body = $streamFactory->createStream(JsonHelper::encode($payload));

        return $factory
            ->createServerRequest('POST', '/api/auth/register/verify-otp', ['REMOTE_ADDR' => '203.0.113.50'])
            ->withBody($body)
            ->withHeader('Content-Type', 'application/json');
    }

    private function okHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new ResponseFactory())->createResponse(200);
            }
        };
    }
}
