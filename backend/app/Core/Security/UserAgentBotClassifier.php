<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security;

/**
 * Classifies HTTP User-Agent strings into human vs bot traffic.
 *
 * Used by analytics (reporting) and firewall (optional blocking of scraper tools).
 */
final class UserAgentBotClassifier
{
    /**
     * @var list<array{name: string, kind: string, pattern: string, block: bool}>
     */
    private const KNOWN_BOTS = [
        ['name' => 'Googlebot', 'kind' => 'search', 'pattern' => 'googlebot', 'block' => false],
        ['name' => 'Bingbot', 'kind' => 'search', 'pattern' => 'bingbot', 'block' => false],
        ['name' => 'DuckDuckBot', 'kind' => 'search', 'pattern' => 'duckduckbot', 'block' => false],
        ['name' => 'YandexBot', 'kind' => 'search', 'pattern' => 'yandexbot', 'block' => false],
        ['name' => 'Baiduspider', 'kind' => 'search', 'pattern' => 'baiduspider', 'block' => false],
        ['name' => 'Applebot', 'kind' => 'search', 'pattern' => 'applebot', 'block' => false],
        ['name' => 'Facebook', 'kind' => 'social', 'pattern' => 'facebookexternalhit|facebot', 'block' => false],
        ['name' => 'Twitterbot', 'kind' => 'social', 'pattern' => 'twitterbot', 'block' => false],
        ['name' => 'LinkedInBot', 'kind' => 'social', 'pattern' => 'linkedinbot', 'block' => false],
        ['name' => 'Slackbot', 'kind' => 'social', 'pattern' => 'slackbot', 'block' => false],
        ['name' => 'WhatsApp', 'kind' => 'social', 'pattern' => 'whatsapp', 'block' => false],
        ['name' => 'TelegramBot', 'kind' => 'social', 'pattern' => 'telegrambot', 'block' => false],
        ['name' => 'Uptime monitor', 'kind' => 'monitor', 'pattern' => 'uptimerobot|pingdom|statuscake|site24x7|gtmetrix', 'block' => false],
        ['name' => 'curl', 'kind' => 'tool', 'pattern' => 'curl/', 'block' => true],
        ['name' => 'Wget', 'kind' => 'tool', 'pattern' => 'wget', 'block' => true],
        ['name' => 'Python requests', 'kind' => 'tool', 'pattern' => 'python-requests|python-urllib', 'block' => true],
        ['name' => 'Scrapy', 'kind' => 'tool', 'pattern' => 'scrapy', 'block' => true],
        ['name' => 'Go HTTP client', 'kind' => 'tool', 'pattern' => 'go-http-client', 'block' => true],
        ['name' => 'Java HTTP', 'kind' => 'tool', 'pattern' => 'java/', 'block' => true],
        ['name' => 'libwww-perl', 'kind' => 'tool', 'pattern' => 'libwww-perl', 'block' => true],
        ['name' => 'HTTPClient', 'kind' => 'tool', 'pattern' => 'httpclient', 'block' => true],
        ['name' => 'Headless Chrome', 'kind' => 'tool', 'pattern' => 'headlesschrome', 'block' => true],
        ['name' => 'sqlmap', 'kind' => 'malicious', 'pattern' => 'sqlmap', 'block' => true],
        ['name' => 'nikto', 'kind' => 'malicious', 'pattern' => 'nikto', 'block' => true],
        ['name' => 'masscan', 'kind' => 'malicious', 'pattern' => 'masscan', 'block' => true],
        ['name' => 'zgrab', 'kind' => 'malicious', 'pattern' => 'zgrab', 'block' => true],
    ];

    public static function classify(?string $userAgent): BotClassification
    {
        $ua = trim($userAgent ?? '');
        if ($ua === '') {
            return new BotClassification('bot', 'Empty user-agent', 'tool', true);
        }

        $lower = strtolower($ua);
        foreach (self::KNOWN_BOTS as $bot) {
            if (preg_match('~' . $bot['pattern'] . '~i', $lower) === 1) {
                return new BotClassification(
                    'bot',
                    $bot['name'],
                    $bot['kind'],
                    $bot['block']
                );
            }
        }

        if (preg_match('/\b(bot|spider|crawler|archiver|preview)\b/i', $ua) === 1) {
            return new BotClassification('bot', self::extractGenericBotName($ua), 'generic', false);
        }

        return new BotClassification('human');
    }

    private static function extractGenericBotName(string $userAgent): string
    {
        if (preg_match('/^([^\s(\/;]+)/', $userAgent, $matches) === 1) {
            $candidate = trim($matches[1]);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return 'Unknown bot';
    }
}
