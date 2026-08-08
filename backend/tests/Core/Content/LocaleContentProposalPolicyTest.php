<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Content;

use PaginiumCMS\Core\Content\LocaleContentProposalPolicy;
use PaginiumCMS\Core\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

final class LocaleContentProposalPolicyTest extends TestCase
{
    public function testRejectsAutomatedPublishProposal(): void
    {
        $policy = new LocaleContentProposalPolicy();

        $this->expectException(ValidationException::class);
        $policy->assertDoesNotAutoPublish(['status' => 'published'], 'ai');
    }

    public function testAllowsManualPublishWithoutAutomatedSource(): void
    {
        $policy = new LocaleContentProposalPolicy();
        $policy->assertDoesNotAutoPublish(['status' => 'published'], null);

        $this->addToAssertionCount(1);
    }
}
