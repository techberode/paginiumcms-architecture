<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Editor;

use PaginiumCMS\Core\Editor\Services\TrustedContentAuditLogger;
use PaginiumCMS\Core\Editor\Services\TrustedContentDetector;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;
use PaginiumCMS\Tests\Http\TestCase;

final class TrustedContentAuditLoggerTest extends TestCase
{
    public function testLogsTrustedMarkdownSave(): void
    {
        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('editor', array_merge($settings->group('editor'), [
            'auditTrustedContent' => true,
        ]));

        $audit = $this->container()->get(SecurityAuditStore::class);
        $logger = new TrustedContentAuditLogger(
            $settings,
            new TrustedContentDetector(),
            $audit
        );

        $user = (new User())
            ->setEmail('admin@test.local')
            ->setRoles(['ADMIN']);
        $markdown = "Intro\n\n:::html-safe\n<div>OK</div>\n:::\n";

        $logger->logContentSave($user, 'article', 'demo-post', 'markdown', $markdown);

        $events = $audit->list(['type' => 'trusted_content_save'], 5);
        $this->assertNotEmpty($events);
        $latest = $events[0];
        $this->assertSame('trusted_content_save', $latest['type'] ?? null);
        $meta = is_array($latest['metadata'] ?? null) ? $latest['metadata'] : [];
        $this->assertSame('1', $meta['html_safe_blocks'] ?? null);
    }

    public function testSkipsWhenAuditDisabled(): void
    {
        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('editor', array_merge($settings->group('editor'), [
            'auditTrustedContent' => false,
        ]));

        $audit = $this->container()->get(SecurityAuditStore::class);
        $before = count($audit->list([], 20));

        $logger = new TrustedContentAuditLogger(
            $settings,
            new TrustedContentDetector(),
            $audit
        );

        $logger->logContentSave(null, 'page', 'home', 'markdown', ":::html-safe\n<div></div>\n:::");

        $this->assertSame($before, count($audit->list([], 20)));
    }
}
