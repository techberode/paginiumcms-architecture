<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Messages\Contracts\MessageRepositoryInterface;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MessageController
{
    public function __construct(
        private MessageRepositoryInterface $messageRepository,
        private JsonResponder $json
    ) {
    }

    public function listMessages(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $messages = array_map(
            fn ($message) => $message->jsonSerialize(),
            $this->messageRepository->findAll()
        );

        return $this->json->success($response, [
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
            return $this->json->error($response, Lang::get('not_found', [], 'messages'), 404);
        }

        $data = json_decode((string) $request->getBody(), true);
        $isRead = is_array($data) && array_key_exists('isRead', $data)
            ? (bool) $data['isRead']
            : true;

        $message->markRead($isRead);

        try {
            $this->messageRepository->update($message);
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }

        return $this->json->success($response, $message->jsonSerialize(), 200, Lang::get('updated', [], 'messages'));
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
            return $this->json->error($response, Lang::get('not_found', [], 'messages'), 404);
        }

        return $this->json->success($response, null, 200, Lang::get('deleted', [], 'messages'));
    }
}
