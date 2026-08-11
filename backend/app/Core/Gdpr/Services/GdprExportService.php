<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Gdpr\Services;

use PaginiumCMS\Modules\Comments\Models\Comment;
use PaginiumCMS\Modules\Comments\Services\CommentsRepository;
use PaginiumCMS\Modules\Messages\Models\ContactMessage;
use PaginiumCMS\Modules\Messages\Services\MessageRepository;
use PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface;
use PaginiumCMS\Modules\Security\Models\User;
use RuntimeException;
use ZipArchive;

/**
 * Aggregates flat-file personal data for a CMS user account (It.80e).
 */
final class GdprExportService
{
    public function __construct(
        private CommentsRepository $comments,
        private MessageRepository $messages,
        private NewsletterRepositoryInterface $newsletter,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildExport(User $user): array
    {
        $email = strtolower(trim($user->getEmail()));
        $displayName = trim($user->getName());

        $matchedComments = $this->comments->findAll([
            'email' => $email,
            'authorName' => $displayName !== '' ? $displayName : null,
        ]);

        return [
            'exportedAt' => date('c'),
            'schemaVersion' => 1,
            'subjectUserId' => $user->getId(),
            'profile' => $user->jsonSerialize(),
            'comments' => array_map(
                static fn (Comment $comment): array => $comment->jsonSerialize(),
                $matchedComments
            ),
            'newsletter' => $this->newsletter->findByEmail($email),
            'contactMessages' => array_map(
                static fn (ContactMessage $message): array => $message->jsonSerialize(),
                $this->messages->findByEmail($email)
            ),
            'limits' => [
                'note' => 'This export covers primary flat-file stores only. Backups, access logs, security audit events, and analytics may retain historical identifiers until rotation or purge.',
            ],
        ];
    }

    /**
     * Builds a ZIP archive containing export.json and returns the absolute temp path.
     */
    public function buildZipArchive(User $user): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZipArchive PHP extension is required.');
        }

        $payload = $this->buildExport($user);
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Failed to encode GDPR export payload.');
        }

        $tempPath = sys_get_temp_dir() . '/gdpr_export_' . $user->getId() . '_' . uniqid('', true) . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Failed to create GDPR export archive.');
        }

        $filename = 'gdpr-export-' . $user->getId() . '.json';
        $zip->addFromString($filename, $json);
        $zip->close();

        return $tempPath;
    }
}
