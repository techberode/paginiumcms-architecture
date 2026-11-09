<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Forms;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Forms\Services\FormDefinitionPolicy;
use PaginiumCMS\Core\Forms\Services\FormDefinitionRepository;
use PHPUnit\Framework\TestCase;

final class FormDefinitionRepositoryTest extends TestCase
{
    private string $baseDir;

    private FormDefinitionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_forms_' . uniqid('', true);
        mkdir($this->baseDir . '/data', 0777, true);
        $validator = new FileValidator($this->baseDir);
        $this->repository = new FormDefinitionRepository(
            new FormDefinitionPolicy(),
            new FileReader($validator),
            new FileWriter($validator)
        );
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->baseDir);
        parent::tearDown();
    }

    public function testCreatesPresetAndListsMarkup(): void
    {
        $saved = $this->repository->createFromPreset('contact-lite');
        $this->assertSame('contact-lite', $saved['id']);
        $this->assertTrue($saved['enabled']);

        $list = $this->repository->list();
        $this->assertCount(1, $list);
        $this->assertSame('[form id="contact-lite" /]', $list[0]['markup']);
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $path . '/' . $item;
            is_dir($full) ? $this->removeTree($full) : @unlink($full);
        }
        @rmdir($path);
    }
}
