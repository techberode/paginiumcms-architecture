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
use PaginiumCMS\Modules\Security\Services\UserAvatarService;
use PaginiumCMS\Modules\Security\Services\UserProfileFields;
use PaginiumCMS\Modules\Security\Services\UserRepository;
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
    ) {
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
