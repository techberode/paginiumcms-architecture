<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use InvalidArgumentException;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\RegistrationInviteService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Superadmin/admin one-time registration links (It.93o-8).
 */
final class RegistrationInvitesController
{
    public function __construct(
        private RegistrationInviteService $invites,
        private JsonResponder $json,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, ['invites' => $this->invites->list()]);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = RequestJsonBody::decode($request) ?? [];
        $email = is_string($body['email'] ?? null) ? $body['email'] : '';
        $teamId = is_string($body['teamId'] ?? null) ? $body['teamId'] : '';
        $sendMail = (bool) ($body['sendMail'] ?? true);
        $source = is_string($body['source'] ?? null) ? $body['source'] : 'admin';
        $actor = $request->getAttribute('user');
        $createdBy = $actor instanceof User ? $actor->getId() : '';

        try {
            $issued = $this->invites->issue($email, $teamId, $createdBy, $sendMail, $source);
        } catch (ValidationException $exception) {
            return $this->json->validation($response, 'Validation failed', $exception->getErrors());
        } catch (InvalidArgumentException $exception) {
            return $this->json->validation($response, 'Validation failed', ['email' => $exception->getMessage()]);
        }

        return $this->json->success($response, $issued, 201);
    }

    /**
     * @param array<string, string> $args
     */
    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $this->invites->revoke((string) ($args['id'] ?? ''));
        } catch (InvalidArgumentException $exception) {
            if ($exception->getMessage() === 'Invite not found') {
                return $this->json->error($response, $exception->getMessage(), 404);
            }

            return $this->json->validation($response, 'Validation failed', ['id' => $exception->getMessage()]);
        }

        return $this->json->success($response, ['removed' => true]);
    }
}
