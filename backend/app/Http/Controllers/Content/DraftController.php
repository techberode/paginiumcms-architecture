<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Content;

use PaginiumCMS\Core\Drafts\Contracts\DraftManagerInterface;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PaginiumCMS\Support\JsonHelper;

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
    public function __construct(private DraftManagerInterface $drafts)
    {
    }

    /**
     * @param array<string, string> $args
 */public function save(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $user = $this->resolveUser($request);
        if ($user === null) {
            return $this->jsonError($response, 'Neprihlásený používateľ', 401);
        }

        $type = (string) ($args['type'] ?? 'page');
        $slug = (string) ($args['slug'] ?? '');
        $payload = $this->parseJsonBody($request);

        $draft = $this->drafts->save($type, $slug, $payload, $user->getId());

        return $this->jsonSuccess($response, $draft->jsonSerialize(), 'Koncept uložený');
    }

    /**
     * @param array<string, string> $args
 */public function load(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $type = (string) ($args['type'] ?? 'page');
        $slug = (string) ($args['slug'] ?? '');

        $draft = $this->drafts->get($type, $slug);

        if ($draft === null) {
            return $this->jsonError($response, 'Koncept neexistuje', 404);
        }

        return $this->jsonSuccess($response, $draft->jsonSerialize());
    }

    /**
     * @param array<string, string> $args
 */public function discard(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $type = (string) ($args['type'] ?? 'page');
        $slug = (string) ($args['slug'] ?? '');

        $this->drafts->discard($type, $slug);

        return $this->jsonSuccess($response, null, 'Koncept zahodený');
    }

    // === Blok: Pomocné metódy (jednotný JSON vzor) ===

    private function resolveUser(ServerRequestInterface $request): ?User
    {
        $user = $request->getAttribute('user');

        return $user instanceof User ? $user : null;
    }

    /**
     * @return array<int|string, mixed>
 */private function parseJsonBody(ServerRequestInterface $request): array
    {
        $data = json_decode((string) $request->getBody(), true);

        return is_array($data) ? $data : [];
    }

    private function jsonSuccess(
        ResponseInterface $response,
        mixed $data,
        ?string $message = null,
        int $status = 200
    ): ResponseInterface {
        $payload = ['success' => true, 'data' => $data];
        if ($message !== null) {
            $payload['message'] = $message;
        }

        $response->getBody()->write(JsonHelper::encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    private function jsonError(ResponseInterface $response, string $message, int $status = 400): ResponseInterface
    {
        $response->getBody()->write(JsonHelper::encode([
            'success' => false,
            'error' => $message,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
