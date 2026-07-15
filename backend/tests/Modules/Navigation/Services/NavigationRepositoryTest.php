<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Navigation\Services;

use PaginiumCMS\Core\FlatFile\Models\Navigation;
use PaginiumCMS\Core\FlatFile\Models\NavigationItem;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Modules\Navigation\Services\NavigationRepository;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class NavigationRepositoryTest extends TestCase
{
    private NavigationRepository $repository;

    protected function setUp(): void
    {
        vfsStream::setup('storage', null, ['content' => []]);
        $root = vfsStream::url('storage/content');

        $validator = new FileValidator($root);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        $this->repository = new NavigationRepository($reader, $writer);
    }

    public function testLoadReturnsDefaultNavigationWhenMissing(): void
    {
        $navigation = $this->repository->load();

        $this->assertGreaterThanOrEqual(4, $navigation->count());
        $this->assertSame('Home', $navigation->getItems()[0]->getLabel());
    }

    public function testSaveAndLoadRoundTrip(): void
    {
        $item = new NavigationItem('Services', '/services');
        $item->setOrder(5);
        $this->repository->save(new Navigation([$item]));

        $loaded = $this->repository->load();
        $this->assertCount(1, $loaded->getItems());
        $this->assertSame('Services', $loaded->getItems()[0]->getLabel());
    }
}
