<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Http\Support\BulkBatchResult;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Messages\Contracts\MessageRepositoryInterface;
use PaginiumCMS\Modules\Messages\Models\ContactMessage;
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
        return $this->updateMessage($request, $response, $args);
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function updateMessage(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'] ?? '';
        $message = $this->messageRepository->findById($id);
        if ($message === null) {
            return $this->json->error($response, Lang::get('not_found', [], 'messages'), 404);
        }

        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'messages'), 400);
        }

        if (array_key_exists('isRead', $data)) {
            $message->markRead((bool) $data['isRead']);
        }
        if (array_key_exists('isProcessed', $data)) {
            $message->markProcessed((bool) $data['isProcessed']);
        }
        if (array_key_exists('isArchived', $data)) {
            $message->markArchived((bool) $data['isArchived']);
        }
        if (array_key_exists('priority', $data)) {
            $message->setPriority((string) $data['priority']);
        }

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

    public function bulkAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'messages'), 400);
        }

        $ids = $this->normalizeIds($data['ids'] ?? null);
        $action = (string) ($data['action'] ?? '');

        if ($ids === []) {
            return $this->json->error($response, Lang::get('ids_required', [], 'messages'), 400);
        }

        if (!in_array($action, ['read', 'processed', 'archive', 'delete'], true)) {
            return $this->json->error($response, Lang::get('invalid_action', [], 'messages'), 422);
        }

        $batch = new BulkBatchResult();

        foreach ($ids as $id) {
            if ($action === 'delete') {
                try {
                    $this->messageRepository->delete($id);
                    $batch->addSuccess($id);
                } catch (FlatFileException) {
                    $batch->addFailure($id, Lang::get('not_found', [], 'messages'));
                }

                continue;
            }

            $message = $this->messageRepository->findById($id);
            if ($message === null) {
                $batch->addFailure($id, Lang::get('not_found', [], 'messages'));

                continue;
            }

            try {
                if ($action === 'read') {
                    $message->markRead(true);
                } elseif ($action === 'processed') {
                    $message->markProcessed(true)->markRead(true);
                } elseif ($action === 'archive') {
                    $message->markArchived(true);
                }

                $this->messageRepository->update($message);
                $batch->addSuccess($id);
            } catch (FlatFileException $e) {
                $batch->addFailure($id, $e->getMessage());
            }
        }

        return $this->json->success(
            $response,
            $batch->toArray(),
            200,
            Lang::get('bulk_updated', [], 'messages')
        );
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    private function normalizeIds(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn ($id) => trim((string) $id), $value),
            static fn (string $id) => $id !== ''
        ));
    }
}
