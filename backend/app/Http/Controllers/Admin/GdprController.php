<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Gdpr\GdprPseudonym;
use PaginiumCMS\Core\Gdpr\Services\GdprAnonymizeService;
use PaginiumCMS\Core\Gdpr\Services\GdprExportService;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Support\LogSanitizer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Psr7\Stream;

/**
 * Admin GDPR export and anonymization for CMS user accounts (It.80e).
 */
final class GdprController
{
    public function __construct(
        private UserRepository $users,
        private GdprExportService $exportService,
        private GdprAnonymizeService $anonymizeService,
        private SecurityAuditStore $audit,
        private JsonResponder $json,
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function export(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $target = $this->resolveUser((string) ($args['id'] ?? ''));
        if ($target === null) {
            return $this->json->error($response, 'Používateľ neexistuje', 404);
        }

        $actor = $this->resolveActor($request);
        $query = $request->getQueryParams();
        $format = strtolower(trim((string) ($query['format'] ?? 'json')));

        $this->audit->append(
            'gdpr_export',
            'INFO',
            'GDPR data export requested for user account',
            $actor?->getId(),
            $actor?->getEmail(),
            null,
            [
                'target_user_id' => $target->getId(),
                'target_email' => LogSanitizer::value($target->getEmail()),
                'format' => $format,
            ]
        );

        if ($format === 'zip') {
            try {
                $zipPath = $this->exportService->buildZipArchive($target);
            } catch (RuntimeException $e) {
                return $this->json->error($response, $e->getMessage(), 500);
            }

            $handle = fopen($zipPath, 'rb');
            if ($handle === false) {
                @unlink($zipPath);

                return $this->json->error($response, 'Nepodarilo sa otvoriť export', 500);
            }

            $filename = 'gdpr-export-' . $target->getId() . '.zip';
            $stream = new Stream($handle);
            register_shutdown_function(static function () use ($zipPath): void {
                @unlink($zipPath);
            });

            return $response
                ->withHeader('Content-Type', 'application/zip')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->withHeader('Content-Length', (string) filesize($zipPath))
                ->withBody($stream)
                ->withStatus(200);
        }

        return $this->json->success($response, [
            'export' => $this->exportService->buildExport($target),
        ]);
    }

    /**
     * @param array<string, string> $args
     */
    public function anonymize(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $targetId = (string) ($args['id'] ?? '');
        $actor = $this->resolveActor($request);
        if ($actor === null) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        if ($actor->getId() === $targetId) {
            return $this->json->error($response, 'Nemôžete anonymizovať vlastný účet', 400);
        }

        $target = $this->resolveUser($targetId);
        if ($target === null) {
            return $this->json->error($response, 'Používateľ neexistuje', 404);
        }

        if ($target->isSuperAdmin() && $this->countSuperAdmins() <= 1) {
            return $this->json->error($response, 'Nemôžete anonymizovať posledného super administrátora', 400);
        }

        if (GdprPseudonym::isAnonymizedEmail($target->getEmail())) {
            return $this->json->error($response, 'Účet je už anonymizovaný', 409);
        }

        $body = $this->parseJsonBody($request);
        if (($body['confirm'] ?? false) !== true) {
            return $this->json->error($response, 'Vyžaduje sa potvrdenie (confirm: true)', 422);
        }

        try {
            $result = $this->anonymizeService->anonymize($target);
        } catch (RuntimeException $e) {
            return $this->json->error($response, $e->getMessage(), 409);
        }

        $this->audit->append(
            'gdpr_anonymize',
            'WARNING',
            'GDPR anonymization applied to user account',
            $actor->getId(),
            $actor->getEmail(),
            null,
            [
                'target_user_id' => $result['userId'],
                'pseudonym' => $result['pseudonym'],
                'comments_updated' => $result['commentsUpdated'],
                'contact_messages_updated' => $result['contactMessagesUpdated'],
                'newsletter_updated' => $result['newsletterUpdated'],
            ]
        );

        return $this->json->success($response, [
            'result' => $result,
        ], 200, 'Účet bol anonymizovaný');
    }

    private function resolveActor(ServerRequestInterface $request): ?User
    {
        $actor = $request->getAttribute('user');

        return $actor instanceof User ? $actor : null;
    }

    private function resolveUser(string $id): ?User
    {
        if ($id === '') {
            return null;
        }

        return $this->users->findById($id);
    }

    private function countSuperAdmins(): int
    {
        $count = 0;
        foreach ($this->users->findAll() as $user) {
            if ($user instanceof User && $user->isSuperAdmin()) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonBody(ServerRequestInterface $request): array
    {
        $raw = (string) $request->getBody();
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
