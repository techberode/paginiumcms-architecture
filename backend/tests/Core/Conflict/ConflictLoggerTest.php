<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Conflict;

use PaginiumCMS\Core\Conflict\Models\ConflictRecord;
use PaginiumCMS\Core\Conflict\Services\ConflictLogger;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PHPUnit\Framework\TestCase;

/**
 * Testy flat-file logu konfliktov (Iterácia 3).
 * Reálny dočasný adresár – ConflictLogger používa fopen('c+') + flock.
 */
class ConflictLoggerTest extends TestCase
{
    private string $baseDir;
    private ConflictLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_conflict_test_' . uniqid();
        mkdir($this->baseDir, 0777, true);

        $validator = new FileValidator($this->baseDir);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        $this->logger = new ConflictLogger($reader, $writer, 'data/conflicts.json', 3);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testRecordAndRetrieve(): void
    {
        $this->logger->record(ConflictRecord::create('page:o-nas', 'user_1', 'Ján', 'rev-a', 'rev-b'));

        $records = $this->logger->getRecent();

        $this->assertCount(1, $records);
        $this->assertSame('page:o-nas', $records[0]->getResourceId());
    }

    public function testNewestFirst(): void
    {
        $this->logger->record(ConflictRecord::create('page:prva', 'u', 'U', 'a', 'b'));
        $this->logger->record(ConflictRecord::create('page:druha', 'u', 'U', 'a', 'b'));

        $records = $this->logger->getRecent();

        $this->assertSame('page:druha', $records[0]->getResourceId(), 'Najnovší konflikt musí byť prvý');
        $this->assertSame('page:prva', $records[1]->getResourceId());
    }

    public function testMaxRecordsCap(): void
    {
        // maxRecords = 3; pridáme 5, ostať majú len 3 najnovšie.
        for ($i = 1; $i <= 5; $i++) {
            $this->logger->record(ConflictRecord::create("page:$i", 'u', 'U', 'a', 'b'));
        }

        $records = $this->logger->getRecent();

        $this->assertCount(3, $records);
        $this->assertSame('page:5', $records[0]->getResourceId());
        $this->assertSame('page:3', $records[2]->getResourceId());
    }

    public function testLimitParameter(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->logger->record(ConflictRecord::create("page:$i", 'u', 'U', 'a', 'b'));
        }

        $this->assertCount(1, $this->logger->getRecent(1));
    }

    public function testClear(): void
    {
        $this->logger->record(ConflictRecord::create('page:o-nas', 'u', 'U', 'a', 'b'));
        $this->assertCount(1, $this->logger->getRecent());

        $this->logger->clear();

        $this->assertCount(0, $this->logger->getRecent());
    }

    public function testPersistsAcrossInstances(): void
    {
        $this->logger->record(ConflictRecord::create('page:o-nas', 'u', 'U', 'a', 'b'));

        // Nová inštancia nad tým istým adresárom musí vidieť zapísaný záznam.
        $validator = new FileValidator($this->baseDir);
        $fresh = new ConflictLogger(new FileReader($validator), new FileWriter($validator), 'data/conflicts.json', 3);

        $this->assertCount(1, $fresh->getRecent());
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
