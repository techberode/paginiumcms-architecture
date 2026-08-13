<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Content;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\Drafts\Contracts\DraftManagerInterface;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Exception\AuthorizationException;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\ContentPathAclGuard;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * === Controller: DraftController ===
 * HTTP vrstva auto-save konceptov (/api/drafts/*).
 *
 *  - PUT    /api/drafts/{type}/{slug}  : uloženie konceptu (volané frontendom každých 60 s)
 *  - GET    /api/drafts/{type}/{slug}  : načítanie konceptu (obnova rozpracovaného obsahu)
 *  - DELETE /api/drafts/{type}/{slug}  : zahodenie konceptu (po publikovaní / na požiadanie)
 */
final class DraftController
{
    public function __construct(
        private DraftManagerInterface $drafts,
        private JsonResponder $json,
        private ContentPathAclGuard $pathAcl
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function save(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $user = $this->resolveUser($request);
        if ($user === null) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        $type = (string) ($args['type'] ?? 'page');
        $slug = (string) ($args['slug'] ?? '');

        $denied = $this->denyUnlessPathAllowed($request, $response, $type, $slug, 'content:edit');
        if ($denied !== null) {
            return $denied;
        }

        $payload = $this->parseJsonBody($request);

        $draft = $this->drafts->save($type, $slug, $payload, $user->getId());

        return $this->json->success($response, $draft->jsonSerialize(), 200, 'Koncept uložený');
    }

    /**
     * @param array<string, string> $args
     */
    public function load(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $type = (string) ($args['type'] ?? 'page');
        $slug = (string) ($args['slug'] ?? '');

        if (!$this->pathAcl->canAccess(
            $this->resolveUser($request),
            $this->pathAcl->contentPathFromSlug($type, $slug),
            'content:edit'
        )) {
            return $this->json->error($response, 'Koncept neexistuje', 404);
        }

        $draft = $this->drafts->get($type, $slug);

        if ($draft === null) {
            return $this->json->error($response, 'Koncept neexistuje', 404);
        }

        return $this->json->success($response, $draft->jsonSerialize());
    }

    /**
     * @param array<string, string> $args
     */
    public function discard(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $type = (string) ($args['type'] ?? 'page');
        $slug = (string) ($args['slug'] ?? '');

        $denied = $this->denyUnlessPathAllowed($request, $response, $type, $slug, 'content:edit');
        if ($denied !== null) {
            return $denied;
        }

        $this->drafts->discard($type, $slug);

        return $this->json->success($response, null, 200, 'Koncept zahodený');
    }

    private function resolveUser(ServerRequestInterface $request): ?User
    {
        $user = $request->getAttribute('user');

        return $user instanceof User ? $user : null;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function parseJsonBody(ServerRequestInterface $request): array
    {
        $data = RequestJsonBody::decode($request);

        return is_array($data) ? $data : [];
    }

    private function denyUnlessPathAllowed(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $type,
        string $slug,
        string $permission
    ): ?ResponseInterface {
        try {
            $this->pathAcl->requireAccess(
                $this->resolveUser($request),
                $this->pathAcl->contentPathFromSlug($type, $slug),
                $permission
            );
        } catch (AuthorizationException $e) {
            return $this->json->error($response, $e->getMessage(), 403);
        }

        return null;
    }
}
