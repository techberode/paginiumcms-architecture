<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\ComingSoon;

use InvalidArgumentException;
use PaginiumCMS\Core\ComingSoon\Services\ComingSoonRepository;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PHPUnit\Framework\TestCase;

final class ComingSoonRepositoryTest extends TestCase
{
    private string $baseDir;
    private ComingSoonRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_soon_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);
        $validator = new FileValidator($this->baseDir);
        $this->repository = new ComingSoonRepository(new FileReader($validator), new FileWriter($validator));
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->baseDir);
        parent::tearDown();
    }

    public function testCreateAndPublicPresent(): void
    {
        $created = $this->repository->create([
            'contentKind' => 'page',
            'slug' => 'naradie',
            'title' => 'Prenájom náradia',
            'subtitle' => 'Čoskoro',
            'publishAt' => time() + 3600,
            'embedOnPage' => true,
        ]);

        $this->assertSame(ComingSoonRepository::SCHEMA, $created['schema']);
        $this->assertMatchesRegularExpression('/^soon_[a-f0-9]{10}$/', $created['id']);
        $this->assertSame('naradie', $created['slug']);

        $public = $this->repository->presentPublic($created, time());
        $this->assertFalse($public['isLive']);
        $this->assertGreaterThan(0, $public['remainingSeconds']);
        $this->assertArrayNotHasKey('userId', $public);
    }

    public function testRejectsDuplicateSlug(): void
    {
        $this->repository->create([
            'contentKind' => 'page',
            'slug' => 'tools',
            'publishAt' => time() + 60,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->repository->create([
            'contentKind' => 'page',
            'slug' => 'tools',
            'publishAt' => time() + 120,
        ]);
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $items = scandir($path);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $path . '/' . $item;
            if (is_dir($full)) {
                $this->removeTree($full);
            } else {
                unlink($full);
            }
        }
        rmdir($path);
    }
}
