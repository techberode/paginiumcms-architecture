<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Backup\Models;

use PaginiumCMS\Core\Backup\Models\BackupMetadata;
use PHPUnit\Framework\TestCase;

class BackupMetadataTest extends TestCase
{
    public function testCreateMetadata(): void
    {
        $metadata = new BackupMetadata();
        $metadata->setName('Test Backup');
        $metadata->setSize(1024);
        $metadata->setFilePath('/path/to/backup.zip');
        $metadata->setIncludes(['content', 'config']);
        $metadata->setStatus('completed');

        $this->assertNotEmpty($metadata->getId());
        $this->assertEquals('Test Backup', $metadata->getName());
        $this->assertEquals(1024, $metadata->getSize());
        $this->assertEquals('/path/to/backup.zip', $metadata->getFilePath());
        $this->assertEquals(['content', 'config'], $metadata->getIncludes());
        $this->assertEquals('completed', $metadata->getStatus());
        $this->assertTrue($metadata->isCompleted());
        $this->assertFalse($metadata->isFailed());
    }

    public function testGetSizeFormatted(): void
    {
        $metadata = new BackupMetadata();
        
        $metadata->setSize(500);
        $this->assertEquals('500 B', $metadata->getSizeFormatted());
        
        $metadata->setSize(2048);
        $this->assertEquals('2 KB', $metadata->getSizeFormatted());
        
        $metadata->setSize(1048576);
        $this->assertEquals('1 MB', $metadata->getSizeFormatted());
        
        $metadata->setSize(1073741824);
        $this->assertEquals('1 GB', $metadata->getSizeFormatted());
    }

    public function testJsonSerialize(): void
    {
        $metadata = new BackupMetadata();
        $metadata->setName('Test Backup');
        $metadata->setSize(1024);
        $metadata->setFilePath('/path/to/backup.zip');
        $metadata->setStatus('completed');

        $data = $metadata->jsonSerialize();
        
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('createdAt', $data);
        $this->assertArrayHasKey('size', $data);
        $this->assertArrayHasKey('sizeFormatted', $data);
        $this->assertArrayHasKey('filePath', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertEquals('Test Backup', $data['name']);
        $this->assertEquals('completed', $data['status']);
    }

    public function testStatusMethods(): void
    {
        $metadata = new BackupMetadata();
        
        $metadata->setStatus('in_progress');
        $this->assertFalse($metadata->isCompleted());
        $this->assertFalse($metadata->isFailed());
        
        $metadata->setStatus('completed');
        $this->assertTrue($metadata->isCompleted());
        $this->assertFalse($metadata->isFailed());
        
        $metadata->setStatus('failed');
        $this->assertFalse($metadata->isCompleted());
        $this->assertTrue($metadata->isFailed());
    }
}
