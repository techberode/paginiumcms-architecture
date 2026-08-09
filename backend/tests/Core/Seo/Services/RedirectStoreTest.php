<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Seo\Services;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\Seo\Services\RedirectStore;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class RedirectStoreTest extends TestCase
{
    private RedirectStore $store;

    protected function setUp(): void
    {
        vfsStream::setup('root', null, ['data' => []]);
        $root = vfsStream::url('root');
        $validator = new FileValidator($root);
        $reader = new FileReader($validator);
        $this->store = new RedirectStore($reader);
    }

    public function testCreateAndMatch301(): void
    {
        $rule = $this->store->create('/blog/old-slug', '/articles/new-slug', 301, 'Renamed');

        $this->assertSame('/blog/old-slug', $rule['from']);
        $this->assertSame('/articles/new-slug', $rule['to']);

        $match = $this->store->match('/blog/old-slug');
        $this->assertNotNull($match);
        $this->assertSame('/articles/new-slug', $match['to']);
        $this->assertSame(301, $match['status']);
    }

    public function testRejectsExternalTarget(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->store->create('/old', 'https://evil.example/phish', 301);
    }

    public function testRejectsSelfLoop(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->store->create('/same', '/same', 301);
    }

    public function testRejectsChainLoop(): void
    {
        $this->store->create('/a', '/b', 301);
        $this->store->create('/b', '/c', 301);

        $this->expectException(InvalidArgumentException::class);
        $this->store->create('/c', '/a', 301);
    }

    public function testDisabledRuleDoesNotMatch(): void
    {
        $rule = $this->store->create('/disabled', '/target', 302);
        $this->store->update($rule['id'], ['enabled' => false]);

        $this->assertNull($this->store->match('/disabled'));
    }

    public function testDeleteRemovesRule(): void
    {
        $rule = $this->store->create('/gone', '/target', 301);
        $this->store->delete($rule['id']);

        $this->assertNull($this->store->match('/gone'));
        $this->assertSame([], $this->store->listRules());
    }
}
