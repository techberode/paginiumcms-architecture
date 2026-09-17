<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use InvalidArgumentException;
use PaginiumCMS\Core\Mail\Services\DomainMailService;
use PaginiumCMS\Core\Security\Services\EncryptionUnavailableException;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Domain IMAP inbox (It.93m). Permission `mail:read-own`. Own mailbox only.
 */
final class MailController
{
    public function __construct(
        private DomainMailService $mail,
        private JsonResponder $json,
    ) {
    }

    public function status(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        return $this->json->success($response, $this->mail->status($user));
    }

    public function savePassword(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $body = RequestJsonBody::decode($request) ?? [];
        $password = is_string($body['password'] ?? null) ? $body['password'] : '';

        try {
            $this->mail->savePassword($user, $password);
        } catch (InvalidArgumentException $exception) {
            return $this->mailValidation($response, $exception);
        } catch (EncryptionUnavailableException $exception) {
            return $this->json->error($response, $exception->getMessage(), 503);
        }

        return $this->json->success($response, ['saved' => true]);
    }

    public function addAccount(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $body = RequestJsonBody::decode($request) ?? [];
        $mailbox = is_string($body['mailbox'] ?? null) ? $body['mailbox'] : '';
        $password = is_string($body['password'] ?? null) ? $body['password'] : '';

        try {
            $this->mail->addAccount($user, $mailbox, $password);
        } catch (InvalidArgumentException $exception) {
            return $this->mailValidation($response, $exception);
        } catch (EncryptionUnavailableException $exception) {
            return $this->json->error($response, $exception->getMessage(), 503);
        }

        return $this->json->success($response, ['added' => true], 201);
    }

    public function removeAccount(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $body = RequestJsonBody::decode($request) ?? [];
        $mailbox = is_string($body['mailbox'] ?? null) ? $body['mailbox'] : '';

        try {
            $this->mail->removeAccount($user, $mailbox);
        } catch (InvalidArgumentException $exception) {
            return $this->mailValidation($response, $exception);
        }

        return $this->json->success($response, ['removed' => true]);
    }

    public function selectAccount(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $body = RequestJsonBody::decode($request) ?? [];
        $mailbox = is_string($body['mailbox'] ?? null) ? $body['mailbox'] : '';

        try {
            $this->mail->selectAccount($user, $mailbox);
        } catch (InvalidArgumentException $exception) {
            return $this->mailValidation($response, $exception);
        }

        return $this->json->success($response, ['mailbox' => $mailbox]);
    }

    public function folders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->run($request, $response, fn (User $user): array => $this->mail->folders($user));
    }

    public function createFolder(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $body = RequestJsonBody::decode($request) ?? [];
        $name = is_string($body['name'] ?? null) ? $body['name'] : '';

        try {
            $this->mail->createFolder($user, $name);
        } catch (InvalidArgumentException $exception) {
            return $this->mailValidation($response, $exception);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 503);
        }

        return $this->json->success($response, ['created' => true], 201);
    }

    public function deleteFolder(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $body = RequestJsonBody::decode($request) ?? [];
        $name = is_string($body['name'] ?? null) ? $body['name'] : $this->folderFromQuery($request);

        try {
            $this->mail->deleteFolder($user, $name);
        } catch (InvalidArgumentException $exception) {
            return $this->mailValidation($response, $exception);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 503);
        }

        return $this->json->success($response, ['deleted' => true]);
    }

    public function messages(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $folder = $this->folderFromQuery($request);

        return $this->run($request, $response, fn (User $user): array => $this->mail->messages($user, $folder));
    }

    /**
     * @param array<string, string> $args
     */
    public function message(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $folder = $this->folderFromQuery($request);
        $uid = (int) ($args['uid'] ?? 0);

        $allowRemoteImages = $this->allowRemoteImagesFromQuery($request);

        return $this->run(
            $request,
            $response,
            fn (User $user): array => $this->mail->message($user, $folder, $uid, $allowRemoteImages)
        );
    }

    /**
     * @param array<string, string> $args
     */
    public function tag(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $body = RequestJsonBody::decode($request) ?? [];
        $folder = is_string($body['folder'] ?? null) ? $body['folder'] : $this->folderFromQuery($request);
        $tags = is_array($body['tags'] ?? null) ? $body['tags'] : [];
        $clean = [];
        foreach ($tags as $tag) {
            if (is_string($tag)) {
                $clean[] = $tag;
            }
        }

        try {
            $this->mail->tag($user, $folder, (int) ($args['uid'] ?? 0), $clean);
        } catch (InvalidArgumentException $exception) {
            return $this->mailValidation($response, $exception);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 503);
        }

        return $this->json->success($response, ['tagged' => true]);
    }

    /**
     * @param array<string, string> $args
     */
    public function flags(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $body = RequestJsonBody::decode($request) ?? [];
        $folder = is_string($body['folder'] ?? null) ? $body['folder'] : $this->folderFromQuery($request);
        $add = $this->stringList($body['add'] ?? null);
        $remove = $this->stringList($body['remove'] ?? null);

        try {
            $this->mail->changeFlags($user, $folder, (int) ($args['uid'] ?? 0), $add, $remove);
        } catch (InvalidArgumentException $exception) {
            return $this->mailValidation($response, $exception);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 503);
        }

        return $this->json->success($response, ['updated' => true]);
    }

    /**
     * @param array<string, string> $args
     */
    public function hide(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $body = RequestJsonBody::decode($request) ?? [];
        $folder = is_string($body['folder'] ?? null) ? $body['folder'] : $this->folderFromQuery($request);
        $subject = is_string($body['subject'] ?? null) ? $body['subject'] : '';
        $from = is_string($body['from'] ?? null) ? $body['from'] : '';
        $date = is_string($body['date'] ?? null) ? $body['date'] : '';

        try {
            $this->mail->hideMessage($user, $folder, (int) ($args['uid'] ?? 0), $subject, $from, $date);
        } catch (InvalidArgumentException $exception) {
            return $this->mailValidation($response, $exception);
        }

        return $this->json->success($response, ['hidden' => true]);
    }

    /**
     * @param array<string, string> $args
     */
    public function unhide(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $body = RequestJsonBody::decode($request) ?? [];
        $folder = is_string($body['folder'] ?? null) ? $body['folder'] : $this->folderFromQuery($request);

        try {
            $this->mail->unhideMessage($user, $folder, (int) ($args['uid'] ?? 0));
        } catch (InvalidArgumentException $exception) {
            return $this->mailValidation($response, $exception);
        }

        return $this->json->success($response, ['restored' => true]);
    }

    public function emptyLocalTrash(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        try {
            $result = $this->mail->emptyLocalTrash($user);
        } catch (InvalidArgumentException $exception) {
            return $this->mailValidation($response, $exception);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 503);
        }

        return $this->json->success($response, $result);
    }

    /**
     * @param array<string, string> $args
     */
    public function spam(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $body = RequestJsonBody::decode($request) ?? [];
        $folder = is_string($body['folder'] ?? null) ? $body['folder'] : $this->folderFromQuery($request);

        try {
            $this->mail->moveToSpam($user, $folder, (int) ($args['uid'] ?? 0));
        } catch (InvalidArgumentException $exception) {
            return $this->mailValidation($response, $exception);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 503);
        }

        return $this->json->success($response, ['moved' => true]);
    }

    /**
     * @param array<string, string> $args
     */
    public function blockSender(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $body = RequestJsonBody::decode($request) ?? [];
        $folder = is_string($body['folder'] ?? null) ? $body['folder'] : $this->folderFromQuery($request);
        $from = is_string($body['from'] ?? null) ? $body['from'] : '';

        try {
            $result = $this->mail->blockSender($user, $folder, (int) ($args['uid'] ?? 0), $from);
        } catch (InvalidArgumentException $exception) {
            return $this->mailValidation($response, $exception);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 503);
        }

        return $this->json->success($response, $result);
    }

    public function autocleanSpam(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        try {
            $result = $this->mail->autocleanSpam($user);
        } catch (InvalidArgumentException $exception) {
            return $this->mailValidation($response, $exception);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 503);
        }

        return $this->json->success($response, $result);
    }

    public function blockedSenders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        return $this->json->success($response, $this->mail->blockedSenders($user));
    }

    public function signature(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        try {
            $payload = $this->mail->signature($user);
        } catch (InvalidArgumentException $exception) {
            return $this->mailValidation($response, $exception);
        }

        return $this->json->success($response, $payload);
    }

    public function saveSignature(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $body = RequestJsonBody::decode($request) ?? [];

        try {
            $payload = $this->mail->saveSignature($user, $body);
        } catch (InvalidArgumentException $exception) {
            return $this->mailValidation($response, $exception);
        }

        return $this->json->success($response, $payload);
    }

    public function importSignatureProfile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        try {
            $payload = $this->mail->importSignatureFromProfile($user);
        } catch (InvalidArgumentException $exception) {
            return $this->mailValidation($response, $exception);
        }

        return $this->json->success($response, $payload);
    }

    public function unblockSender(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $body = RequestJsonBody::decode($request) ?? [];
        $email = is_string($body['email'] ?? null) ? trim($body['email']) : '';
        if ($email === '') {
            return $this->json->validation($response, 'Validation failed', ['email' => ['Email is required.']]);
        }

        try {
            $result = $this->mail->unblockSender($user, $email);
        } catch (InvalidArgumentException $exception) {
            return $this->mailValidation($response, $exception);
        }

        return $this->json->success($response, $result);
    }

    public function send(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $body = RequestJsonBody::decode($request) ?? [];
        $to = is_string($body['to'] ?? null) ? $body['to'] : '';
        $subject = is_string($body['subject'] ?? null) ? $body['subject'] : '';
        $text = is_string($body['body'] ?? null) ? $body['body'] : '';

        try {
            $result = $this->mail->send($user, $to, $subject, $text);
        } catch (InvalidArgumentException $exception) {
            return $this->mailValidation($response, $exception);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 503);
        }

        return $this->json->success($response, $result);
    }

    public function saveDraft(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $body = RequestJsonBody::decode($request) ?? [];
        $to = is_string($body['to'] ?? null) ? $body['to'] : '';
        $subject = is_string($body['subject'] ?? null) ? $body['subject'] : '';
        $text = is_string($body['body'] ?? null) ? $body['body'] : '';
        $uid = (int) ($body['uid'] ?? 0);

        try {
            $result = $this->mail->saveDraft($user, $to, $subject, $text, $uid);
        } catch (InvalidArgumentException $exception) {
            return $this->mailValidation($response, $exception);
        }

        return $this->json->success($response, $result);
    }

    /**
     * @param array<string, mixed> $args
     */
    public function deleteLocalMessage(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        $folder = $this->folderFromQuery($request);
        $uid = (int) ($args['uid'] ?? 0);

        try {
            $this->mail->deleteLocalMessage($user, $folder, $uid);
        } catch (InvalidArgumentException $exception) {
            return $this->mailValidation($response, $exception);
        }

        return $this->json->success($response, ['deleted' => true]);
    }

    /**
     * @param callable(User): array<string, mixed> $action
     */
    private function run(ServerRequestInterface $request, ResponseInterface $response, callable $action): ResponseInterface
    {
        $user = $this->actor($request);
        if ($user === null) {
            return $this->json->error($response, 'Unauthorized', 401);
        }

        try {
            return $this->json->success($response, $action($user));
        } catch (InvalidArgumentException $exception) {
            return $this->mailValidation($response, $exception);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 503);
        }
    }

    private function mailValidation(ResponseInterface $response, InvalidArgumentException $exception): ResponseInterface
    {
        return $this->json->validation($response, 'Validation failed', ['mail' => [$exception->getMessage()]]);
    }

    private function actor(ServerRequestInterface $request): ?User
    {
        $user = $request->getAttribute('user');

        return $user instanceof User ? $user : null;
    }

    private function allowRemoteImagesFromQuery(ServerRequestInterface $request): bool
    {
        $params = $request->getQueryParams();
        $value = $params['remoteImages'] ?? null;

        return $value === '1' || $value === 1 || $value === 'true';
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                $out[] = $item;
            }
        }

        return $out;
    }

    private function folderFromQuery(ServerRequestInterface $request): string
    {
        $query = $request->getQueryParams();
        $folder = $query['folder'] ?? 'INBOX';

        return is_string($folder) && $folder !== '' ? $folder : 'INBOX';
    }
}
