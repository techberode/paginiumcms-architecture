<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Mail;

use PaginiumCMS\Core\Mail\Services\MailSignatureFieldMerge;
use PaginiumCMS\Core\Mail\Services\MailSignatureRenderer;
use PaginiumCMS\Modules\Security\Models\User;
use PHPUnit\Framework\TestCase;

final class MailSignatureRendererTest extends TestCase
{
    public function testRendersClassicTemplateWithEscapedContent(): void
    {
        $html = MailSignatureRenderer::render('classic', [
            'displayName' => 'Marian <script>',
            'jobTitle' => 'Editor',
            'phone' => '+421 900 000 000',
            'contactEmail' => 'info@paginium.test',
            'bio' => 'Hello & welcome',
            'companyName' => 'PaginiumCMS',
            'website' => 'https://paginium.test',
            'avatarUrl' => '',
        ]);

        $this->assertStringContainsString('Marian &lt;script&gt;', $html);
        $this->assertStringContainsString('Hello &amp; welcome', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testMergeUsesProfileAndMailboxThenOverrides(): void
    {
        $user = new User();
        $user->setName('Admin User');
        $user->setJobTitle('CTO');
        $user->setPhone('+421111');
        $user->setBio('Bio line');

        $fields = MailSignatureFieldMerge::resolve(
            $user,
            'info@paginium.test',
            ['name' => 'Paginium', 'website' => 'https://paginium.test'],
            ['displayName' => 'Support Desk', 'jobTitle' => ''],
            'https://paginium.test'
        );

        $this->assertSame('Support Desk', $fields['displayName']);
        $this->assertSame('CTO', $fields['jobTitle']);
        $this->assertSame('info@paginium.test', $fields['contactEmail']);
        $this->assertSame('Paginium', $fields['companyName']);
    }
}
