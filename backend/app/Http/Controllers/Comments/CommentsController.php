<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Comments;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Modules\Comments\Contracts\CommentsRepositoryInterface;
use PaginiumCMS\Modules\Comments\Models\Comment;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PaginiumCMS\Support\JsonHelper;

class CommentsController
{
    public function __construct(
        private CommentsRepositoryInterface $commentsRepository,
        private SettingsRepositoryInterface $settingsRepository,
        private Validator $validator
    ) {
    }

    public function listPublic(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $filters = [
            'status' => Comment::STATUS_APPROVED,
        ];

        $articleSlug = trim((string) ($params['articleSlug'] ?? $params['articleId'] ?? ''));
        if ($articleSlug !== '') {
            $filters['articleSlug'] = $articleSlug;
        }

        $comments = array_map(
            fn (Comment $comment) => $this->publicShape($comment),
            $this->commentsRepository->findAll($filters)
        );

        return $this->jsonSuccess($response, $comments);
    }

    public function submit(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            return $this->jsonError($response, Lang::get('invalid_payload', [], 'comments'), 400);
        }

        try {
            $validated = $this->validator->validate($data, [
                'articleSlug' => ['required', 'string', 'min:1', 'max:120'],
                'author' => ['required', 'string', 'min:2', 'max:120'],
                'email' => ['email', 'max:255'],
                'content' => ['required', 'string', 'min:3', 'max:2000'],
            ]);
        } catch (ValidationException $e) {
            return $this->jsonValidationError($response, $e);
        }

        $settings = $this->settingsRepository->group('comments');
        if (($settings['enabled'] ?? true) === false) {
            return $this->jsonError($response, Lang::get('disabled', [], 'comments'), 403);
        }

        $user = $request->getAttribute('user');
        if ($user === null && ($settings['allowGuestComments'] ?? true) === false) {
            return $this->jsonError($response, 'Anonymné komentáre sú vypnuté', 403);
        }

        $comment = new Comment(
            (string) $validated['articleSlug'],
            (string) $validated['author'],
            (string) $validated['content']
        );
        $comment->setEmail((string) ($validated['email'] ?? ''));

        if (($settings['requireApproval'] ?? true) === false) {
            $comment->setStatus(Comment::STATUS_APPROVED);
        }

        $this->commentsRepository->save($comment);

        return $this->jsonSuccess(
            $response,
            $this->publicShape($comment),
            Lang::get('submitted', [], 'comments'),
            201
        );
    }

    public function listAdmin(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $filters = [];

        $articleSlug = trim((string) ($params['articleSlug'] ?? $params['articleId'] ?? ''));
        if ($articleSlug !== '') {
            $filters['articleSlug'] = $articleSlug;
        }

        $status = trim((string) ($params['status'] ?? ''));
        if ($status !== '') {
            $filters['status'] = $status;
        }

        $comments = array_map(
            fn (Comment $comment) => $comment->jsonSerialize(),
            $this->commentsRepository->findAll($filters)
        );

        return $this->jsonSuccess($response, [
            'items' => $comments,
            'count' => count($comments),
        ]);
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'] ?? '';
        $comment = $this->commentsRepository->findById($id);
        if ($comment === null) {
            return $this->jsonError($response, Lang::get('not_found', [], 'comments'), 404);
        }

        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            return $this->jsonError($response, Lang::get('invalid_payload', [], 'comments'), 400);
        }

        if (isset($data['status'])) {
            $status = (string) $data['status'];
            if (!in_array($status, [Comment::STATUS_PENDING, Comment::STATUS_APPROVED, Comment::STATUS_REJECTED], true)) {
                return $this->jsonError($response, Lang::get('invalid_status', [], 'comments'), 422);
            }
            $comment->setStatus($status);
        }

        if (isset($data['content'])) {
            $commentContent = trim((string) $data['content']);
            if ($commentContent === '') {
                return $this->jsonError($response, Lang::get('content_required', [], 'comments'), 422);
            }
            $reflection = new \ReflectionClass($comment);
            $prop = $reflection->getProperty('content');
            $prop->setValue($comment, $commentContent);
        }

        try {
            $this->commentsRepository->update($comment);
        } catch (FlatFileException $e) {
            return $this->jsonError($response, $e->getMessage(), 500);
        }

        return $this->jsonSuccess($response, $comment->jsonSerialize(), Lang::get('updated', [], 'comments'));
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'] ?? '';

        try {
            $this->commentsRepository->delete($id);
        } catch (FlatFileException) {
            return $this->jsonError($response, Lang::get('not_found', [], 'comments'), 404);
        }

        return $this->jsonSuccess($response, null, Lang::get('deleted', [], 'comments'));
    }

    /**
     * @return array<int|string, mixed>
 */private function publicShape(Comment $comment): array
    {
        return [
            'id' => $comment->getId(),
            'articleSlug' => $comment->getArticleSlug(),
            'author' => $comment->getAuthor(),
            'content' => $comment->getContent(),
            'status' => $comment->getStatus(),
            'createdAt' => $comment->getCreatedAt(),
            'approvedAt' => $comment->getApprovedAt(),
        ];
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

    private function jsonValidationError(ResponseInterface $response, ValidationException $e): ResponseInterface
    {
        $response->getBody()->write(JsonHelper::encode([
            'success' => false,
            'error' => Lang::get('validation_failed', [], 'comments'),
            'errors' => $e->getErrors(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withStatus(422)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
