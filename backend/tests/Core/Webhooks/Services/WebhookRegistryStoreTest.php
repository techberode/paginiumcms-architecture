<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Webhooks\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\Security\Services\EncryptionService;
use PaginiumCMS\Core\Webhooks\Services\WebhookRegistryStore;
use PaginiumCMS\Core\Webhooks\WebhookEventCatalog;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class WebhookRegistryStoreTest extends TestCase
{
    private const KEY = 'base64:BGtLQwdzAE7ajivCghMa98DyudMghYZEkXKw5PJ/aUE=';

    private WebhookRegistryStore $store;

    protected function setUp(): void
    {
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';

        vfsStream::setup('root', null, ['data' => []]);
        $root = vfsStream::url('root');
        $validator = new FileValidator($root);
        $reader = new FileReader($validator);
        $encryption = new EncryptionService(self::KEY);
        $this->store = new WebhookRegistryStore($reader, $encryption);
    }

    public function testCreateReturnsCopyOnceSecretAndStoresEncrypted(): void
    {
        $created = $this->store->create(
            'Zapier',
            'https://hooks.example.com/paginium',
            [WebhookEventCatalog::CONTENT_PUBLISHED],
            'admin-1'
        );

        $this->assertSame(64, strlen($created['secret']));
        $this->assertStringStartsWith('wh_', $created['record']['id']);
        $this->assertTrue(str_starts_with($created['record']['secretEnc'], 'enc:'));

        $listed = $this->store->listMetadata();
        $this->assertCount(1, $listed);
        $this->assertArrayNotHasKey('secretEnc', $listed[0]);
        $this->assertArrayNotHasKey('secret', $listed[0]);
    }

    public function testEnabledForEventFiltersBySubscription(): void
    {
        $this->store->create('A', 'https://a.example/hook', [WebhookEventCatalog::CONTENT_PUBLISHED], 'admin');
        $this->store->create('B', 'https://b.example/hook', [WebhookEventCatalog::CONTENT_UPDATED], 'admin');

        $published = $this->store->enabledForEvent(WebhookEventCatalog::CONTENT_PUBLISHED);
        $this->assertCount(1, $published);
        $this->assertSame('A', $published[0]['label']);

        $updated = $this->store->enabledForEvent(WebhookEventCatalog::CONTENT_UPDATED);
        $this->assertCount(1, $updated);
        $this->assertSame('B', $updated[0]['label']);
    }

    public function testRotateSecretChangesVerifier(): void
    {
        $created = $this->store->create(
            'Rotate me',
            'https://hooks.example.com/r',
            [WebhookEventCatalog::CONTENT_UPDATED],
            'admin'
        );

        $before = $created['record']['secretEnc'];
        $rotated = $this->store->rotateSecret($created['record']['id']);

        $this->assertNotSame($before, $rotated['record']['secretEnc']);
        $this->assertNotSame($created['secret'], $rotated['secret']);
    }

    protected function tearDown(): void
    {
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';

        parent::tearDown();
    }

    public function testRejectsBlockedUrlInProductionGuard(): void
    {
        putenv('APP_ENV=production');
        $_ENV['APP_ENV'] = 'production';

        $this->expectException(InvalidArgumentException::class);
        $this->store->create(
            'Bad',
            'http://127.0.0.1/hook',
            [WebhookEventCatalog::CONTENT_PUBLISHED],
            'admin'
        );
    }
}
