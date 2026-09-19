<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\PublicApi;

use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Core\Validation\VisitorEmailGuard;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Modules\Messages\Models\ContactMessage;
use PaginiumCMS\Modules\Messages\Services\MessageDeskService;
use PaginiumCMS\Modules\Security\Services\PublishedStaffDirectory;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Support\Lang;
use PaginiumCMS\Support\LogSanitizer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Opt-in staff cards + staff-chat into Messages (It.93o / 93o-3).
 */
final class StaffDirectoryController
{
    public function __construct(
        private PublishedStaffDirectory $directory,
        private JsonResponder $json,
        private ?UserRepository $users = null,
        private ?MessageDeskService $desk = null,
        private ?Validator $validator = null,
        private ?VisitorEmailGuard $visitorEmail = null,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $user = is_string($query['user'] ?? null) ? trim($query['user']) : '';
        $type = is_string($query['type'] ?? null) ? trim($query['type']) : '';
        $team = is_string($query['team'] ?? null) ? trim($query['team']) : '';

        if ($user !== '' || $type !== '' || $team !== '') {
            return $this->json->success($response, [
                'cards' => $this->directory->resolveCards(
                    $user !== '' ? $user : null,
                    $type !== '' ? $type : null,
                    $team !== '' ? $team : null
                ),
            ]);
        }

        return $this->json->success($response, $this->directory->publicLists());
    }

    /**
     * @param array<string, string> $args
     */
    public function message(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if ($this->users === null || $this->desk === null || $this->validator === null) {
            return $this->json->error($response, 'Staff chat is unavailable', 503);
        }

        $staff = $this->users->findById(trim((string) ($args['id'] ?? '')));
        if ($staff === null || !$this->directory->canPublicChat($staff)) {
            return $this->json->error($response, 'Staff chat is not available', 404);
        }

        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'contact'), 400);
        }

        if (trim((string) ($data['_hp'] ?? '')) !== '') {
            return $this->json->success($response, ['id' => 'hp_' . bin2hex(random_bytes(8))], 201);
        }

        try {
            $validated = $this->validator->validate($data, [
                'name' => ['required', 'string', 'min:2', 'max:120'],
                'email' => ['required', 'email', 'max:255'],
                'message' => ['required', 'string', 'min:10', 'max:5000'],
            ]);
        } catch (ValidationException $exception) {
            return $this->json->validation($response, Lang::get('validation_failed', [], 'contact'), $exception->getErrors());
        }

        try {
            $email = $this->visitorEmail !== null
                ? $this->visitorEmail->normalize((string) $validated['email'], 'contact')
                : strtolower(trim((string) $validated['email']));
        } catch (ValidationException $exception) {
            return $this->json->validation($response, Lang::get('validation_failed', [], 'contact'), $exception->getErrors());
        }

        $body = LogSanitizer::value((string) $validated['message'], 5000);
        $message = new ContactMessage(
            LogSanitizer::value((string) $validated['name'], 120),
            $email,
            $body
        );
        $message->setSubject('Staff chat: ' . LogSanitizer::value($staff->getName(), 80));
        $message->setStaffUserId($staff->getId());
        $message->setChannel('staff-chat');
        $message->setIp((string) ($request->getServerParams()['REMOTE_ADDR'] ?? 'unknown'));
        $result = $this->desk->ingest($message);

        return $this->json->success(
            $response,
            ['id' => $result['message']->getId(), 'appended' => $result['appended']],
            201,
            Lang::get('submitted', [], 'contact')
        );
    }
}
