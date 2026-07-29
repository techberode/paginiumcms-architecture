<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Webhooks;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Tests\Http\TestCase;

final class GitHubReleaseWebhookControllerTest extends TestCase
{
    private const SECRET = 'test-webhook-secret-63';

    public function testReleaseWebhookRequiresValidSignature(): void
    {
        $this->enableWebhookDeploy();

        $body = $this->releasePayload('v2.1.0-beta.18');
        $response = $this->handleRequest(
            $this->signedRequest($body, 'wrong-secret')
        );

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testReleaseWebhookQueuesDeployOnPublishedRelease(): void
    {
        $this->enableWebhookDeploy();

        $body = $this->releasePayload('v2.1.0-beta.18');
        $response = $this->handleRequest(
            $this->signedRequest($body, self::SECRET)
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertTrue($data['data']['queued']);
        $this->assertSame('v2.1.0-beta.18', $data['data']['ref']);
    }

    public function testReleaseWebhookIgnoresNonPublishedAction(): void
    {
        $this->enableWebhookDeploy();

        $body = JsonHelper::encode([
            'action' => 'deleted',
            'release' => ['tag_name' => 'v2.1.0-beta.18'],
        ]);
        $response = $this->handleRequest(
            $this->signedRequest($body, self::SECRET, 'release')
        );
        $data = $this->getJsonResponse($response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertTrue($data['data']['ignored']);
    }

    public function testReleaseWebhookForbiddenWhenDisabled(): void
    {
        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('systemUpdate', array_merge($settings->group('systemUpdate'), [
            'webhookDeployEnabled' => false,
            'githubWebhookSecret' => self::SECRET,
        ]));

        $body = $this->releasePayload('v2.1.0-beta.18');
        $response = $this->handleRequest(
            $this->signedRequest($body, self::SECRET)
        );

        $this->assertSame(403, $response->getStatusCode());
    }

    private function enableWebhookDeploy(): void
    {
        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('systemUpdate', array_merge($settings->group('systemUpdate'), [
            'deployEnabled' => true,
            'allowDeployTags' => true,
            'allowDeployMain' => false,
            'webhookDeployEnabled' => true,
            'githubWebhookSecret' => self::SECRET,
        ]));
    }

    private function releasePayload(string $tag): string
    {
        return JsonHelper::encode([
            'action' => 'published',
            'release' => ['tag_name' => $tag],
        ]);
    }

    /**
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    private function signedRequest(string $body, string $secret, string $event = 'release')
    {
        $signature = 'sha256=' . hash_hmac('sha256', $body, $secret);

        $request = $this->createJsonRequest('POST', '/api/webhooks/github/release', null, [
            'X-GitHub-Event' => $event,
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Delivery' => 'test-delivery-id',
        ]);

        return $request->withBody(
            (new \Slim\Psr7\Factory\StreamFactory())->createStream($body)
        )->withHeader('Content-Type', 'application/json');
    }
}
