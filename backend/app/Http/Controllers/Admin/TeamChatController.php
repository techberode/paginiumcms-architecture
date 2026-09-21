<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use InvalidArgumentException;
use PaginiumCMS\Core\Security\Upload\UploadPolicyException;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Teams\Services\TeamChatStore;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

final class TeamChatController
{
    public function __construct(
        private TeamChatStore $chat,
        private JsonResponder $json,
    ) {
    }

    public function rooms(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $actor = $request->getAttribute('user');
        if (!$actor instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        return $this->json->success($response, ['rooms' => $this->chat->roomsFor($actor)]);
    }

    public function inbox(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $actor = $request->getAttribute('user');
        if (!$actor instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        $inbox = $this->chat->inboxFor($actor);

        return $this->json->success($response, $inbox);
    }

    /**
     * @param array<string, string> $args
     */
    public function messages(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $actor = $request->getAttribute('user');
        if (!$actor instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        try {
            $items = $this->chat->messages((string) ($args['teamId'] ?? ''), $actor);
        } catch (InvalidArgumentException $exception) {
            return $this->json->error($response, $exception->getMessage(), 404);
        }

        return $this->json->success($response, ['messages' => $items]);
    }

    /**
     * @param array<string, string> $args
     */
    public function search(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $actor = $request->getAttribute('user');
        if (!$actor instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }
        $query = is_string($request->getQueryParams()['q'] ?? null)
            ? trim((string) $request->getQueryParams()['q'])
            : '';

        try {
            $items = $this->chat->search((string) ($args['teamId'] ?? ''), $actor, $query);
        } catch (InvalidArgumentException $exception) {
            return $this->json->error(
                $response,
                $exception->getMessage(),
                $this->historyErrorStatus($exception)
            );
        }

        return $this->json->success($response, ['messages' => $items]);
    }

    /**
     * @param array<string, string> $args
     */
    public function exportArchive(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $actor = $request->getAttribute('user');
        if (!$actor instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        try {
            $archive = $this->chat->exportArchive((string) ($args['teamId'] ?? ''), $actor);
        } catch (InvalidArgumentException $exception) {
            return $this->json->error(
                $response,
                $exception->getMessage(),
                $this->historyErrorStatus($exception, 404)
            );
        }

        $name = str_replace(['"', "\r", "\n"], '', $archive['filename']);
        $response->getBody()->write($archive['json']);

        return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $name . '"')
            ->withHeader('X-Content-Type-Options', 'nosniff');
    }

    /**
     * @param array<string, string> $args
     */
    public function importArchive(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $actor = $request->getAttribute('user');
        if (!$actor instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }
        $body = RequestJsonBody::decode($request);
        if (!is_array($body)) {
            return $this->json->error($response, 'JSON body is required', 400);
        }

        try {
            $imported = $this->chat->importArchive((string) ($args['teamId'] ?? ''), $actor, $body);
        } catch (InvalidArgumentException $exception) {
            return $this->json->error(
                $response,
                $exception->getMessage(),
                $this->historyErrorStatus($exception)
            );
        }

        return $this->json->success($response, ['imported' => $imported]);
    }

    /**
     * @param array<string, string> $args
     */
    public function clearHistory(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $actor = $request->getAttribute('user');
        if (!$actor instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        try {
            $deleted = $this->chat->clearHistory((string) ($args['teamId'] ?? ''), $actor);
        } catch (InvalidArgumentException $exception) {
            return $this->json->error(
                $response,
                $exception->getMessage(),
                $this->historyErrorStatus($exception, 404)
            );
        }

        return $this->json->success($response, ['deleted' => $deleted]);
    }

    private function historyErrorStatus(InvalidArgumentException $exception, int $default = 400): int
    {
        if ($exception->getMessage() === TeamChatStore::HISTORY_ACCESS_DENIED) {
            return 403;
        }

        return $default;
    }

    /**
     * @param array<string, string> $args
     */
    public function post(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $actor = $request->getAttribute('user');
        if (!$actor instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }
        $body = RequestJsonBody::decode($request) ?? [];

        try {
            $message = $this->chat->post(
                (string) ($args['teamId'] ?? ''),
                $actor,
                (string) ($body['kind'] ?? TeamChatStore::KIND_TEXT),
                (string) ($body['body'] ?? ''),
                (string) ($body['language'] ?? '')
            );
        } catch (InvalidArgumentException $exception) {
            return $this->json->validation($response, 'Validation failed', ['body' => $exception->getMessage()]);
        }

        return $this->json->success($response, ['message' => $message], 201);
    }

    /**
     * @param array<string, string> $args
     */
    public function upload(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $actor = $request->getAttribute('user');
        if (!$actor instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }
        $file = $request->getUploadedFiles()['file'] ?? null;
        if (!$file instanceof UploadedFileInterface || $file->getError() !== UPLOAD_ERR_OK) {
            return $this->json->error($response, 'File is required', 400);
        }

        try {
            $message = $this->chat->attach(
                (string) ($args['teamId'] ?? ''),
                $actor,
                $file->getClientFilename() ?? 'upload.bin',
                (string) $file->getStream(),
                $file->getClientMediaType() ?? 'application/octet-stream'
            );
        } catch (InvalidArgumentException | UploadPolicyException $exception) {
            return $this->json->error($response, $exception->getMessage(), 400);
        }

        return $this->json->success($response, ['message' => $message], 201);
    }

    /**
     * @param array<string, string> $args
     */
    public function download(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $actor = $request->getAttribute('user');
        if (!$actor instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        try {
            $file = $this->chat->download(
                (string) ($args['teamId'] ?? ''),
                (string) ($args['fileId'] ?? ''),
                $actor
            );
        } catch (InvalidArgumentException $exception) {
            return $this->json->error($response, $exception->getMessage(), 404);
        }
        if ($file === null) {
            return $this->json->error($response, 'File not found', 404);
        }

        $name = str_replace(['"', "\r", "\n"], '', $file['name']);
        $response->getBody()->write($file['binary']);

        return $response
            ->withStatus(200)
            ->withHeader('Content-Type', $file['mime'] !== '' ? $file['mime'] : 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $name . '"')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Content-Security-Policy', 'sandbox');
    }
}
