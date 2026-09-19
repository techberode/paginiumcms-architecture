<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Comments\Services;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Effective comment policy for an article (global settings + per-article overrides).
 */
final class CommentPolicyResolver
{
    public function __construct(
        private SettingsRepositoryInterface $settingsRepository,
        private ContentRepositoryInterface $contentRepository,
        private CommentSpamHeuristicService $spamHeuristic,
    ) {
    }

    /**
     * @return array{
     *     enabled: bool,
     *     requireApproval: bool,
     *     allowGuestComments: bool,
     *     ratingEnabled: bool
     * }
     */
    public function resolveForArticle(string $articleSlug): array
    {
        $settings = $this->settingsRepository->group('comments');

        $enabled = ($settings['enabled'] ?? true) !== false;
        $requireApproval = ($settings['requireApproval'] ?? true) !== false;
        $allowGuestComments = ($settings['allowGuestComments'] ?? true) !== false;
        $ratingEnabled = ($settings['ratingEnabled'] ?? false) === true;

        $content = $this->contentRepository->findBySlug($articleSlug, 'article');
        if (!$content instanceof Article) {
            return [
                'enabled' => $enabled,
                'requireApproval' => $requireApproval,
                'allowGuestComments' => $allowGuestComments,
                'ratingEnabled' => $ratingEnabled,
            ];
        }

        if (!$content->getCommentsEnabled()) {
            $enabled = false;
        }

        $approvalOverride = $content->getCommentsRequireApproval();
        if ($approvalOverride !== null) {
            $requireApproval = $approvalOverride;
        }

        $guestsOverride = $content->getCommentsAllowGuests();
        if ($guestsOverride !== null) {
            $allowGuestComments = $guestsOverride;
        }

        $ratingOverride = $content->getCommentsRatingEnabled();
        if ($ratingOverride !== null) {
            $ratingEnabled = $ratingOverride;
        }

        return [
            'enabled' => $enabled,
            'requireApproval' => $requireApproval,
            'allowGuestComments' => $allowGuestComments,
            'ratingEnabled' => $ratingEnabled,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function evaluateSubmission(array $payload, string $clientIp): CommentSpamVerdict
    {
        return $this->spamHeuristic->evaluate($payload, $clientIp);
    }

    public function recordSubmission(string $clientIp): void
    {
        $this->spamHeuristic->recordSubmission($clientIp);
    }
}
