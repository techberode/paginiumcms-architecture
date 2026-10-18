<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Versioning\Services;

use PaginiumCMS\Core\CodeEditor\Services\CodeEditorLogger;
use PaginiumCMS\Core\CodeEditor\Services\DiffGenerator;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Versioning\Services\EnhancedVersionManager;
use PaginiumCMS\Support\JsonHelper;
use PHPUnit\Framework\TestCase;

final class EnhancedVersionManagerRetentionTest extends TestCase
{
    public function testPurgeVersionsOlderThanKeepsLatestRegardlessOfAge(): void
    {
        $baseDir = sys_get_temp_dir() . '/paginium-version-retention-' . uniqid('', true);
        mkdir($baseDir . '/data/versions', 0777, true);

        $contentId = 'page_about';
        $old = [
            'contentId' => $contentId,
            'contentType' => 'page',
            'version' => 1,
            'content' => 'v1',
            'frontMatter' => '',
            'createdAt' => '2020-01-01 00:00:00',
            'createdBy' => 'u1',
            'message' => '',
        ];
        $mid = $old;
        $mid['version'] = 2;
        $mid['content'] = 'v2';
        $mid['createdAt'] = '2020-06-01 00:00:00';

        $latest = $old;
        $latest['version'] = 3;
        $latest['content'] = 'v3';
        $latest['createdAt'] = '2020-01-02 00:00:00';

        $versionsDir = $baseDir . '/data/versions';
        file_put_contents($versionsDir . '/' . $contentId . '_1.json', JsonHelper::encode($old));
        file_put_contents($versionsDir . '/' . $contentId . '_2.json', JsonHelper::encode($mid));
        file_put_contents($versionsDir . '/' . $contentId . '_3.json', JsonHelper::encode($latest));

        $validator = new FileValidator($baseDir);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $logger = $this->createMock(CodeEditorLogger::class);

        $manager = new EnhancedVersionManager(
            $reader,
            $writer,
            new DiffGenerator(),
            $logger,
            'data/versions',
            50
        );

        $deleted = $manager->purgeVersionsOlderThan(30, 1);

        $this->assertSame(2, $deleted);
        $remaining = $manager->getVersions($contentId);
        $this->assertCount(1, $remaining);
        $this->assertSame(3, $remaining[0]->getVersion());

        foreach (glob($versionsDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($versionsDir);
        @rmdir($baseDir . '/data');
        @rmdir($baseDir);
    }
}
