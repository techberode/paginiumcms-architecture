<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Security;

use PaginiumCMS\Core\Security\UserAgentBotClassifier;
use PHPUnit\Framework\TestCase;

final class UserAgentBotClassifierTest extends TestCase
{
    public function testClassifiesHumanBrowser(): void
    {
        $result = UserAgentBotClassifier::classify(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        );

        $this->assertSame('human', $result->visitorType);
        $this->assertFalse($result->isBot());
    }

    public function testClassifiesGooglebot(): void
    {
        $result = UserAgentBotClassifier::classify(
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
        );

        $this->assertTrue($result->isBot());
        $this->assertSame('Googlebot', $result->botName);
        $this->assertSame('search', $result->botKind);
        $this->assertFalse($result->shouldBlock);
    }

    public function testClassifiesCurlAsBlockableTool(): void
    {
        $result = UserAgentBotClassifier::classify('curl/8.4.0');

        $this->assertTrue($result->isBot());
        $this->assertSame('curl', $result->botName);
        $this->assertSame('tool', $result->botKind);
        $this->assertTrue($result->shouldBlock);
    }

    public function testEmptyUserAgentIsBlockableBot(): void
    {
        $result = UserAgentBotClassifier::classify('');

        $this->assertTrue($result->isBot());
        $this->assertSame('Empty user-agent', $result->botName);
        $this->assertTrue($result->shouldBlock);
    }
}
