<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\SystemUpdate;

use PaginiumCMS\Core\SystemUpdate\Services\SystemUpdateVersionMatcher;
use PHPUnit\Framework\TestCase;

final class SystemUpdateVersionMatcherTest extends TestCase
{
    private SystemUpdateVersionMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->matcher = new SystemUpdateVersionMatcher();
    }

    public function testCurrentWhenReleaseTagsMatch(): void
    {
        $result = $this->matcher->evaluate(
            '2.1.0-beta.14',
            'v2.1.0-beta.14',
            'v2.1.0-beta.14',
            'abc1234',
            'abc1234full',
            0
        );

        $this->assertSame('current', $result['status']);
    }

    public function testUpdateAvailableWhenReleaseTagDiffers(): void
    {
        $result = $this->matcher->evaluate(
            '2.1.0-beta.13',
            'v2.1.0-beta.13',
            'v2.1.0-beta.14',
            'aaa',
            'bbb',
            3
        );

        $this->assertSame('update_available', $result['status']);
        $this->assertSame('2.1.0-beta.14', $result['latest_tag']);
    }

    public function testCurrentWhenCommitsMatchWithoutTags(): void
    {
        $result = $this->matcher->evaluate(
            '2.1.0-beta.14',
            '66e83f0-dirty',
            null,
            '66e83f0',
            '66e83f0a2a03248431994',
            0
        );

        $this->assertSame('current', $result['status']);
    }
}
