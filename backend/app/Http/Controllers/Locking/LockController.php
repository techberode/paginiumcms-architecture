<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Locking;

use PaginiumCMS\Core\Locking\Contracts\LockManagerInterface;
use PaginiumCMS\Core\Locking\Exception\LockConflictException;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * === Controller: LockController ===
 * HTTP vrstva systému zamykania obsahu (/api/locks/*).
 *
 * Čašník (tento controller) preberá požiadavky od hosťa (React) a posiela ich
 * kuchárovi (LockManager). Sám nič neukladá – iba prekladá HTTP <-> doménu.
 *
 * Endpointy:
 *  - POST   /api/locks/acquire     : získať zámok (vráti token pre heartbeat)
 *  - POST   /api/locks/heartbeat   : predĺžiť zámok (každých 30 s z frontendu)
 *  - POST   /api/locks/release     : uvoľniť zámok
 *  - GET    /api/locks             : zoznam všetkých zámkov (admin)
 *  - DELETE /api/locks/{resourceId}: vynútené uvoľnenie (admin)
 */
final class LockController
{
    public function __construct(private LockManagerInterface $locks)
    {
    }

    // === Blok: Získanie zámku ===

    public function acquire(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->resolveUser($request);
        if ($user === null) {
            return $this->jsonError($response, 'Neprihlásený používateľ', 401);
        }

        $data = $this->parseJsonBody($request);
        $resourceId = trim((string) ($data['resourceId'] ?? ''));

        if ($resourceId === '') {
            return $this->jsonError($response, 'Chýba resourceId', 400);
        }

        try {
            $lock = $this->locks->acquire($resourceId, $user);

            // Token vraciame IBA vlastníkovi pri získaní zámku – frontend ho použije pri heartbeat/release.
            return $this->jsonSuccess($response, [
                'lock' => $lock->jsonSerialize(),
                'token' => $lock->getToken(),
                'ttl' => $lock->getExpiresAt() - $lock->getLastHeartbeat(),
            ], 'Zámok získaný', 201);
        } catch (LockConflictException $e) {
            return $this->jsonConflict($response, $e);
        }
    }

    // === Blok: Heartbeat (predĺženie) ===

    public function heartbeat(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->parseJsonBody($request);
        $resourceId = trim((string) ($data['resourceId'] ?? ''));
        $token = (string) ($data['token'] ?? '');

        if ($resourceId === '' || $token === '') {
            return $this->jsonError($response, 'Chýba resourceId alebo token', 400);
        }

        try {
            $lock = $this->locks->heartbeat($resourceId, $token);

            return $this->jsonSuccess($response, [
                'lock' => $lock->jsonSerialize(),
                'ttl' => $lock->getExpiresAt() - $lock->getLastHeartbeat(),
            ], 'Zámok obnovený');
        } catch (LockConflictException $e) {
            return $this->jsonConflict($response, $e);
        }
    }

    // === Blok: Uvoľnenie ===

    public function release(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->parseJsonBody($request);
        $resourceId = trim((string) ($data['resourceId'] ?? ''));
        $token = (string) ($data['token'] ?? '');

        if ($resourceId === '' || $token === '') {
            return $this->jsonError($response, 'Chýba resourceId alebo token', 400);
        }

        $this->locks->release($resourceId, $token);

        return $this->jsonSuccess($response, null, 'Zámok uvoľnený');
    }

    // === Blok: Admin – zoznam a vynútené uvoľnenie ===

    public function listLocks(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $locks = array_map(
            static fn ($lock) => $lock->jsonSerialize(),
            $this->locks->getAllLocks()
        );

        return $this->jsonSuccess($response, $locks);
    }

    /**
     * @param array<string, string> $args
     */
    public function forceRelease(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $resourceId = trim((string) ($args['resourceId'] ?? ''));

        if ($resourceId === '') {
            return $this->jsonError($response, 'Chýba resourceId', 400);
        }

        $this->locks->forceRelease($resourceId);

        return $this->jsonSuccess($response, null, 'Zámok vynútene uvoľnený');
    }

    // === Blok: Pomocné metódy (rovnaký vzor ako ContentController) ===

    private function resolveUser(ServerRequestInterface $request): ?User
    {
        $user = $request->getAttribute('user');

        return $user instanceof User ? $user : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonBody(ServerRequestInterface $request): array
    {
        $data = json_decode((string) $request->getBody(), true);

        return is_array($data) ? $data : [];
    }

    private function jsonConflict(ResponseInterface $response, LockConflictException $e): ResponseInterface
    {
        $response->getBody()->write((string) json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'lock' => $e->getCurrentLock()->jsonSerialize(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withStatus(409)->withHeader('Content-Type', 'application/json; charset=utf-8');
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

        $response->getBody()->write((string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    private function jsonError(ResponseInterface $response, string $message, int $status = 400): ResponseInterface
    {
        $response->getBody()->write((string) json_encode([
            'success' => false,
            'error' => $message,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
