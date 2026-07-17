<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Locking;

use PaginiumCMS\Core\Locking\Contracts\LockManagerInterface;
use PaginiumCMS\Core\Locking\Exception\LockConflictException;
use PaginiumCMS\Http\Support\JsonResponder;
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
    public function __construct(
        private LockManagerInterface $locks,
        private JsonResponder $json
    ) {
    }

    public function acquire(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->resolveUser($request);
        if ($user === null) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        $data = $this->parseJsonBody($request);
        $resourceId = trim((string) ($data['resourceId'] ?? ''));

        if ($resourceId === '') {
            return $this->json->error($response, 'Chýba resourceId', 400);
        }

        try {
            $lock = $this->locks->acquire($resourceId, $user);

            return $this->json->success($response, [
                'lock' => $lock->jsonSerialize(),
                'token' => $lock->getToken(),
                'ttl' => $lock->getExpiresAt() - $lock->getLastHeartbeat(),
            ], 201, 'Zámok získaný');
        } catch (LockConflictException $e) {
            return $this->json->conflict($response, $e->getMessage(), [
                'lock' => $e->getCurrentLock()->jsonSerialize(),
            ]);
        }
    }

    public function heartbeat(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->parseJsonBody($request);
        $resourceId = trim((string) ($data['resourceId'] ?? ''));
        $token = (string) ($data['token'] ?? '');

        if ($resourceId === '' || $token === '') {
            return $this->json->error($response, 'Chýba resourceId alebo token', 400);
        }

        try {
            $lock = $this->locks->heartbeat($resourceId, $token);

            return $this->json->success($response, [
                'lock' => $lock->jsonSerialize(),
                'ttl' => $lock->getExpiresAt() - $lock->getLastHeartbeat(),
            ], 200, 'Zámok obnovený');
        } catch (LockConflictException $e) {
            return $this->json->conflict($response, $e->getMessage(), [
                'lock' => $e->getCurrentLock()->jsonSerialize(),
            ]);
        }
    }

    public function release(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->parseJsonBody($request);
        $resourceId = trim((string) ($data['resourceId'] ?? ''));
        $token = (string) ($data['token'] ?? '');

        if ($resourceId === '' || $token === '') {
            return $this->json->error($response, 'Chýba resourceId alebo token', 400);
        }

        $this->locks->release($resourceId, $token);

        return $this->json->success($response, null, 200, 'Zámok uvoľnený');
    }

    public function listLocks(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $locks = array_map(
            static fn ($lock) => $lock->jsonSerialize(),
            $this->locks->getAllLocks()
        );

        return $this->json->success($response, $locks);
    }

    /**
     * @param array<string, string> $args
     */
    public function forceRelease(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $resourceId = trim((string) ($args['resourceId'] ?? ''));

        if ($resourceId === '') {
            return $this->json->error($response, 'Chýba resourceId', 400);
        }

        $this->locks->forceRelease($resourceId);

        return $this->json->success($response, null, 200, 'Zámok vynútene uvoľnený');
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
        $data = json_decode((string) $request->getBody(), true);

        return is_array($data) ? $data : [];
    }
}
