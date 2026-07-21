<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\AuditTrail\Services;

use PaginiumCMS\Core\AuditTrail\Services\AuditMessageFormatter;
use PaginiumCMS\Modules\Security\Models\User;
use PHPUnit\Framework\TestCase;

class AuditMessageFormatterTest extends TestCase
{
    private AuditMessageFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new AuditMessageFormatter();
    }

    public function testFormatsContentChangeForNewLogs(): void
    {
        $user = $this->createUser('Maxxim', 'maxxim@webland.fun');

        $message = $this->formatter->format(
            'content_change',
            'update',
            'blog',
            $user,
            [
                'content_type' => 'article',
                'version' => 12,
                'change_summary' => '3 pridaných, 1 odstránených',
                'message' => 'Update article: blog',
            ]
        );

        $this->assertSame(
            'Maxxim upravil článok „blog“ (verzia 12) · 3 pridaných, 1 odstránených',
            $message
        );
    }

    public function testFormatsContentChangeWithTitleAndSlug(): void
    {
        $user = $this->createUser('Admin', 'admin@example.com');

        $message = $this->formatter->format(
            'content_change',
            'update',
            'blog',
            $user,
            [
                'content_type' => 'article',
                'content_title' => 'Ako sme stavali PaginiumCMS',
                'content_slug' => 'blog',
                'version' => 7,
                'change_summary' => '8 pridaných, 1 odstránených',
            ]
        );

        $this->assertSame(
            'Admin upravil článok „Ako sme stavali PaginiumCMS“ (blog) (verzia 7) · 8 pridaných, 1 odstránených',
            $message
        );
    }

    public function testFormatsContentStatusChange(): void
    {
        $message = $this->formatter->format(
            'content_change',
            'status',
            'o-nas',
            null,
            [
                'content_type' => 'page',
                'content_title' => 'O nás',
                'content_slug' => 'o-nas',
                'content_status' => 'published',
                'version' => 3,
            ]
        );

        $this->assertSame(
            'Systém zmenil stav stránku „O nás“ (o-nas) (verzia 3) → publikovaný',
            $message
        );
    }

    public function testFormatsLegacyLogFromContext(): void
    {
        $message = $this->formatter->formatFromLog([
            'category' => 'app',
            'message' => '[CONTENT_CHANGE] UPDATE: blog on maxxim@webland.fun by 2026-07-20 20:44:47',
            'context' => [
                'category' => 'content_change',
                'action' => 'update',
                'target' => 'blog',
                'user' => [
                    'name' => 'Maxxim',
                    'email' => 'maxxim@webland.fun',
                ],
                'metadata' => [
                    'content_type' => 'article',
                    'version' => 5,
                    'message' => 'Update article: blog',
                ],
            ],
        ]);

        $this->assertSame('Maxxim upravil článok „blog“ (verzia 5)', $message);
    }

    public function testPrefersStoredSummary(): void
    {
        $message = $this->formatter->formatFromLog([
            'message' => 'ignored',
            'context' => [
                'summary' => 'Maxxim zmazal stránku „kontakt“ (verzia 3)',
            ],
        ]);

        $this->assertSame('Maxxim zmazal stránku „kontakt“ (verzia 3)', $message);
    }

    private function createUser(string $name, string $email): User
    {
        return (new User())
            ->setEmail($email)
            ->setName($name)
            ->setPasswordHash('hash')
            ->setRoles(['ADMIN']);
    }
}
