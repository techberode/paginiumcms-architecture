<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Comments\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Honeypot + heuristic spam scoring for public comment submissions (It.80c).
 */
final class CommentSpamHeuristicService
{
    private const QUARANTINE_THRESHOLD = 50;

    private const REJECT_THRESHOLD = 80;

    private const MAX_LINKS_BEFORE_SCORE = 2;

    private const LINK_SCORE_EACH = 15;

    private const DISPOSABLE_EMAIL_SCORE = 40;

    private const REPETITION_SCORE = 25;

    private const VELOCITY_SCORE = 30;

    private const DEFAULT_VELOCITY_MAX_PER_HOUR = 5;

    public function __construct(
        private SettingsRepositoryInterface $settingsRepository,
        private DisposableEmailDomainList $disposableDomains,
        private CommentSubmissionVelocityStore $velocityStore,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function evaluate(array $payload, string $clientIp): CommentSpamVerdict
    {
        $honeypot = trim((string) ($payload['_hp'] ?? ''));
        if ($honeypot !== '') {
            return new CommentSpamVerdict(CommentSpamVerdict::ACTION_REJECT_SILENT, 100);
        }

        $settings = $this->settingsRepository->group('comments');
        if (($settings['spamHeuristicsEnabled'] ?? true) === false) {
            return new CommentSpamVerdict(CommentSpamVerdict::ACTION_ALLOW);
        }

        $author = trim((string) ($payload['author'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $content = trim((string) ($payload['content'] ?? ''));

        $score = 0;
        $linkCount = $this->countLinks($content);
        $maxLinks = max(0, (int) ($settings['spamMaxLinks'] ?? self::MAX_LINKS_BEFORE_SCORE));
        if ($linkCount > $maxLinks) {
            $score += ($linkCount - $maxLinks) * self::LINK_SCORE_EACH;
        }

        if ($email !== '' && $this->disposableDomains->isDisposable($email)) {
            $score += self::DISPOSABLE_EMAIL_SCORE;
        }

        if ($this->hasExcessiveRepetition($author . ' ' . $content)) {
            $score += self::REPETITION_SCORE;
        }

        $clientHash = hash('sha256', $clientIp);
        $velocityMax = max(1, (int) ($settings['spamVelocityMaxPerHour'] ?? self::DEFAULT_VELOCITY_MAX_PER_HOUR));
        if ($this->velocityStore->countRecent($clientHash, 1) >= $velocityMax) {
            $score += self::VELOCITY_SCORE;
        }

        $quarantineThreshold = max(1, (int) ($settings['spamQuarantineThreshold'] ?? self::QUARANTINE_THRESHOLD));
        $rejectThreshold = max($quarantineThreshold + 1, (int) ($settings['spamRejectThreshold'] ?? self::REJECT_THRESHOLD));

        if ($score >= $rejectThreshold) {
            return new CommentSpamVerdict(CommentSpamVerdict::ACTION_REJECT, $score);
        }

        if ($score >= $quarantineThreshold) {
            return new CommentSpamVerdict(CommentSpamVerdict::ACTION_QUARANTINE, $score);
        }

        return new CommentSpamVerdict(CommentSpamVerdict::ACTION_ALLOW, $score);
    }

    public function recordSubmission(string $clientIp): void
    {
        $this->velocityStore->record(hash('sha256', $clientIp));
    }

    private function countLinks(string $text): int
    {
        if ($text === '') {
            return 0;
        }

        preg_match_all('/(?:https?:\/\/|www\.)[^\s<>"\'\]]+/i', $text, $matches);

        return count($matches[0]);
    }

    private function hasExcessiveRepetition(string $text): bool
    {
        $normalized = preg_replace('/\s+/u', '', mb_strtolower($text)) ?? '';
        $length = mb_strlen($normalized);
        if ($length < 12) {
            return false;
        }

        $chars = preg_split('//u', $normalized, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false) {
            return false;
        }

        $counts = array_count_values($chars);
        $max = max($counts);

        return ($max / $length) >= 0.55;
    }
}
