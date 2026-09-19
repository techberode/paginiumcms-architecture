<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Auth;

use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\SessionManager;
use PaginiumCMS\Modules\Messages\Services\DeskInboxService;
use PaginiumCMS\Modules\Security\Services\PublishedStaffDirectory;
use PaginiumCMS\Modules\Security\Services\SocialAccountLinkProbe;
use PaginiumCMS\Modules\Security\Services\StaffPresenceStore;
use PaginiumCMS\Modules\Security\Services\UserAvatarService;
use PaginiumCMS\Modules\Security\Services\UserProfileFields;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Support\LogSanitizer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Self-service account profile (It.93o). Session + CSRF; not ADMIN-only.
 */
final class AccountController
{
    public function __construct(
        private UserRepository $users,
        private UserAvatarService $avatars,
        private SessionManager $session,
        private AuthorizationInterface $authorization,
        private Validator $validator,
        private JsonResponder $json,
        private SocialAccountLinkProbe $socialLinkProbe,
        private PublishedStaffDirectory $directory,
        private StaffPresenceStore $presence,
        private DeskInboxService $deskInbox,
    ) {
    }

    public function verifySocialAccount(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        $payload = RequestJsonBody::decode($request) ?? [];
        $platform = strtolower(trim((string) ($payload['platform'] ?? '')));
        $url = trim((string) ($payload['url'] ?? ''));
        if ($platform === '' || $url === '') {
            return $this->json->error($response, 'Platform and URL are required', 400);
        }

        if (!in_array($platform, UserProfileFields::SOCIAL_PLATFORMS, true)) {
            return $this->json->error($response, 'Unsupported platform', 400);
        }

        $result = $this->socialLinkProbe->verify($platform, $url);
        if (!$result['ok']) {
            return $this->json->error($response, LogSanitizer::value($result['message'], 240), 400);
        }

        return $this->json->success($response, [
            'platform' => $platform,
            'normalizedUrl' => $result['normalizedUrl'],
            'verifiedAt' => time(),
            'message' => $result['message'],
            'httpStatus' => $result['httpStatus'],
        ], 200, 'Social link verified');
    }

    public function chatStatus(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        return $this->json->success($response, array_merge(
            $this->directory->chatStatus($user),
            $this->deskInbox->status($user)
        ));
    }

    public function desk(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        $status = $this->deskInbox->status($user);

        return $this->json->success($response, array_merge(
            $this->directory->chatStatus($user),
            $status,
            [
                'items' => $this->deskInbox->items($user),
                'deskBubbleEnabled' => $user->isDeskBubbleEnabled(),
                'deskBubbleAnchor' => $user->getDeskBubbleAnchor(),
                'deskBubbleX' => $user->getDeskBubbleX(),
                'deskBubbleY' => $user->getDeskBubbleY(),
            ]
        ));
    }

    public function updatePresence(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        $payload = RequestJsonBody::decode($request) ?? [];
        $online = (bool) ($payload['online'] ?? false);
        if ($online && !$user->isChatEnabled()) {
            return $this->json->error($response, 'Enable chat on the public card first', 400);
        }

        $this->presence->heartbeat($user->getId(), $online);

        return $this->json->success($response, $this->directory->chatStatus($user), 200, 'Presence updated');
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        $payload = RequestJsonBody::decode($request) ?? [];

        try {
            $identity = $this->validator->validate(
                array_intersect_key($payload, array_flip(['email', 'username'])),
                [
                    'email' => ['email', 'max:255'],
                    'username' => ['string', 'min:2', 'max:64', 'slug'],
                ]
            );

            if (isset($identity['email']) && $identity['email'] !== $user->getEmail()) {
                if ($this->users->existsByEmail($identity['email'])) {
                    return $this->json->error($response, 'E-mail už existuje', 409);
                }
                $user->setEmail($identity['email']);
            }

            if (isset($identity['username'])) {
                $username = strtolower(trim((string) $identity['username']));
                if ($this->users->existsByUsername($username, $user->getId())) {
                    return $this->json->error($response, 'Používateľské meno už existuje', 409);
                }
                $user->setUsername($username);
            }

            UserProfileFields::apply($user, $payload);
            if (array_key_exists('chatEnabled', $payload) && !$user->isChatEnabled()) {
                $this->presence->heartbeat($user->getId(), false);
            }
        } catch (ValidationException $exception) {
            return $this->json->validation($response, 'Validation failed', $exception->getErrors());
        }

        $user->setUpdatedAt(time());
        $this->users->save($user);
        $this->session->updateUser($user);

        return $this->json->success($response, [
            'user' => $this->present($user),
        ], 200, 'Profil bol aktualizovaný');
    }

    public function assignAvatarFromUrl(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        $payload = RequestJsonBody::decode($request) ?? [];
        $url = trim((string) ($payload['url'] ?? $payload['avatarUrl'] ?? ''));
        if ($url === '') {
            return $this->json->error($response, 'URL avataru je povinná', 400);
        }

        try {
            $resolved = $this->avatars->assignFromMediaUrl($user, $url);
            $user->setAvatarUrl($resolved);
            $user->setUpdatedAt(time());
            $this->users->save($user);
            $this->session->updateUser($user);

            return $this->json->success($response, [
                'user' => $this->present($user),
            ], 200, 'Avatar bol aktualizovaný');
        } catch (\Throwable $exception) {
            return $this->json->error($response, $exception->getMessage(), 400);
        }
    }

    public function uploadAvatar(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        $uploadedFiles = $request->getUploadedFiles();
        $file = $uploadedFiles['avatar'] ?? $uploadedFiles['file'] ?? null;
        if (!$file instanceof UploadedFileInterface || $file->getError() !== UPLOAD_ERR_OK) {
            return $this->json->error($response, 'Súbor avataru je povinný', 400);
        }

        try {
            $stream = $file->getStream();
            $contents = $stream->isSeekable() ? $stream->getContents() : (string) $stream;
            $url = $this->avatars->assignFromUpload(
                $user,
                $file->getClientFilename() ?? 'avatar.png',
                $contents,
                $file->getClientMediaType() ?? 'application/octet-stream'
            );
            $user->setAvatarUrl($url);
            $user->setUpdatedAt(time());
            $this->users->save($user);
            $this->session->updateUser($user);

            return $this->json->success($response, [
                'user' => $this->present($user),
            ], 200, 'Avatar bol aktualizovaný');
        } catch (\Throwable $exception) {
            return $this->json->error($response, $exception->getMessage(), 400);
        }
    }

    public function removeAvatar(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        $this->avatars->remove($user);
        $user->setUpdatedAt(time());
        $this->users->save($user);
        $this->session->updateUser($user);

        return $this->json->success($response, [
            'user' => $this->present($user),
        ], 200, 'Avatar bol odstránený');
    }

    private function actor(ServerRequestInterface $request): ?User
    {
        $user = $request->getAttribute('user');

        return $user instanceof User ? $user : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function present(User $user): array
    {
        $payload = $user->jsonSerialize();
        $payload['permissions'] = $this->authorization->permissionsFor($user);

        return $payload;
    }
}
