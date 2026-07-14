<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Drafts;

use PaginiumCMS\Core\Drafts\Services\DraftManager;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

/**
 * Testy auto-save konceptov (Iterácia 2).
 */
class DraftManagerTest extends TestCase
{
    private DraftManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        vfsStream::setup('storage', null, ['content' => ['data' => ['drafts' => []]]]);
        $root = vfsStream::url('storage');

        $validator = new FileValidator($root . '/content');
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        $this->manager = new DraftManager($reader, $writer, 'data/drafts');
    }

    public function testSaveAndGetDraft(): void
    {
        $this->manager->save('page', 'o-nas', [
            'title' => 'O nás',
            'content' => '# Rozpracovaný obsah',
            'status' => 'draft',
            'baseRevision' => 'abc123',
        ], 'user_1');

        $draft = $this->manager->get('page', 'o-nas');

        $this->assertNotNull($draft);
        $this->assertSame('o-nas', $draft->getSlug());
        $this->assertSame('page', $draft->getType());
        $this->assertSame('abc123', $draft->getBaseRevision());
        $this->assertGreaterThan(0, $draft->getSavedAt());
    }

    public function testGetReturnsNullWhenMissing(): void
    {
        $this->assertNull($this->manager->get('page', 'neexistuje'));
        $this->assertFalse($this->manager->exists('page', 'neexistuje'));
    }

    public function testExistsAfterSave(): void
    {
        $this->manager->save('article', 'novinka', ['title' => 'N', 'content' => 'x'], 'user_1');
        $this->assertTrue($this->manager->exists('article', 'novinka'));
    }

    public function testDiscardRemovesDraft(): void
    {
        $this->manager->save('page', 'zmazat', ['title' => 'Z', 'content' => 'x'], 'user_1');
        $this->assertTrue($this->manager->exists('page', 'zmazat'));

        $this->manager->discard('page', 'zmazat');

        $this->assertFalse($this->manager->exists('page', 'zmazat'));
    }

    public function testDiscardMissingIsSafe(): void
    {
        // Nemá vyhodiť výnimku
        $this->manager->discard('page', 'nikdy-neexistovalo');
        $this->assertFalse($this->manager->exists('page', 'nikdy-neexistovalo'));
    }

    public function testInvalidTypeFallsBackToPage(): void
    {
        $this->manager->save('hacker', 'slug', ['title' => 'T', 'content' => 'x'], 'user_1');

        // Neplatný typ sa normalizuje na 'page'
        $this->assertTrue($this->manager->exists('page', 'slug'));
    }

    public function testSlugIsSanitized(): void
    {
        // Nebezpečný slug s traversal znakmi sa očistí – nesmie uniknúť z priečinka.
        $this->manager->save('page', '../../etc/passwd', ['title' => 'T', 'content' => 'x'], 'user_1');

        $draft = $this->manager->get('page', '../../etc/passwd');
        $this->assertNotNull($draft, 'Očistený slug musí byť konzistentný pri save aj get');
    }
}
