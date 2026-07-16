<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Modules\Messages\Contracts\MessageRepositoryInterface;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PaginiumCMS\Support\JsonHelper;

class MessageController
{
    public function __construct(
        private MessageRepositoryInterface $messageRepository
    ) {
    }

    public function listMessages(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $messages = array_map(
            fn ($message) => $message->jsonSerialize(),
            $this->messageRepository->findAll()
        );

        return $this->jsonSuccess($response, [
            'items' => $messages,
            'count' => count($messages),
        ]);
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function markRead(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'] ?? '';
        $message = $this->messageRepository->findById($id);
        if ($message === null) {
            return $this->jsonError($response, Lang::get('not_found', [], 'messages'), 404);
        }

        $data = json_decode((string) $request->getBody(), true);
        $isRead = is_array($data) && array_key_exists('isRead', $data)
            ? (bool) $data['isRead']
            : true;

        $message->markRead($isRead);

        try {
            $this->messageRepository->update($message);
        } catch (FlatFileException $e) {
            return $this->jsonError($response, $e->getMessage(), 500);
        }

        return $this->jsonSuccess($response, $message->jsonSerialize(), Lang::get('updated', [], 'messages'));
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function deleteMessage(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'] ?? '';

        try {
            $this->messageRepository->delete($id);
        } catch (FlatFileException) {
            return $this->jsonError($response, Lang::get('not_found', [], 'messages'), 404);
        }

        return $this->jsonSuccess($response, null, Lang::get('deleted', [], 'messages'));
    }

    private function jsonSuccess(ResponseInterface $response, mixed $data, ?string $message = null, int $status = 200): ResponseInterface
    {
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
