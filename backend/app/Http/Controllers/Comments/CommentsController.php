<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Comments;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Core\Workflow\Services\OtpWorkflowService;
use PaginiumCMS\Http\Support\BulkBatchResult;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Comments\Contracts\CommentsRepositoryInterface;
use PaginiumCMS\Modules\Comments\Models\Comment;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CommentsController
{
    public function __construct(
        private CommentsRepositoryInterface $commentsRepository,
        private SettingsRepositoryInterface $settingsRepository,
        private Validator $validator,
        private OtpWorkflowService $otpWorkflow,
        private JsonResponder $json
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

        return $this->json->success($response, $comments);
    }

    public function submit(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'comments'), 400);
        }

        try {
            $validated = $this->validator->validate($data, [
                'articleSlug' => ['required', 'string', 'min:1', 'max:120'],
                'author' => ['required', 'string', 'min:2', 'max:120'],
                'email' => ['email', 'max:255'],
                'content' => ['required', 'string', 'min:3', 'max:2000'],
            ]);
        } catch (ValidationException $e) {
            return $this->json->validation(
                $response,
                Lang::get('validation_failed', [], 'comments'),
                $e->getErrors()
            );
        }

        $settings = $this->settingsRepository->group('comments');
        if (($settings['enabled'] ?? true) === false) {
            return $this->json->error($response, Lang::get('disabled', [], 'comments'), 403);
        }

        $user = $request->getAttribute('user');
        if ($user === null && ($settings['allowGuestComments'] ?? true) === false) {
            return $this->json->error($response, 'Anonymné komentáre sú vypnuté', 403);
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

        return $this->json->success(
            $response,
            $this->publicShape($comment),
            201,
            Lang::get('submitted', [], 'comments')
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

        return $this->json->success($response, [
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
            return $this->json->error($response, Lang::get('not_found', [], 'comments'), 404);
        }

        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'comments'), 400);
        }

        if (isset($data['status'])) {
            $status = (string) $data['status'];
            if (!in_array($status, [Comment::STATUS_PENDING, Comment::STATUS_APPROVED, Comment::STATUS_REJECTED], true)) {
                return $this->json->error($response, Lang::get('invalid_status', [], 'comments'), 422);
            }

            if (
                $status === Comment::STATUS_APPROVED
                && $comment->getStatus() !== Comment::STATUS_APPROVED
                && $this->otpWorkflow->isCommentApprovalOtpEnabled()
            ) {
                $editor = $request->getAttribute('user');
                if (!$editor instanceof User) {
                    return $this->json->error($response, 'Neprihlásený používateľ', 401);
                }

                try {
                    $otp = $this->otpWorkflow->startCommentApproval($editor, $id);

                    return $this->json->respond($response, [
                        'success' => true,
                        'requires_otp' => true,
                        'message' => 'Overovací kód bol odoslaný na email',
                        'challenge_id' => $otp['challenge_id'],
                        'expires_at' => $otp['expires_at'],
                        'debug_code' => $otp['debug_code'] ?? null,
                    ], 202);
                } catch (\Exception $e) {
                    return $this->json->error($response, $e->getMessage(), 400);
                }
            }

            $comment->setStatus($status);
        }

        if (isset($data['content'])) {
            $commentContent = trim((string) $data['content']);
            if ($commentContent === '') {
                return $this->json->error($response, Lang::get('content_required', [], 'comments'), 422);
            }
            $reflection = new \ReflectionClass($comment);
            $prop = $reflection->getProperty('content');
            $prop->setValue($comment, $commentContent);
        }

        try {
            $this->commentsRepository->update($comment);
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }

        return $this->json->success($response, $comment->jsonSerialize(), 200, Lang::get('updated', [], 'comments'));
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
            return $this->json->error($response, Lang::get('not_found', [], 'comments'), 404);
        }

        return $this->json->success($response, null, 200, Lang::get('deleted', [], 'comments'));
    }

    public function bulkUpdateStatus(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'comments'), 400);
        }

        $ids = $this->normalizeIds($data['ids'] ?? null);
        $status = (string) ($data['status'] ?? '');

        if ($ids === []) {
            return $this->json->error($response, Lang::get('ids_required', [], 'comments'), 400);
        }

        if (!in_array($status, [Comment::STATUS_PENDING, Comment::STATUS_APPROVED, Comment::STATUS_REJECTED], true)) {
            return $this->json->error($response, Lang::get('invalid_status', [], 'comments'), 422);
        }

        $batch = new BulkBatchResult();
        foreach ($ids as $id) {
            $comment = $this->commentsRepository->findById($id);
            if ($comment === null) {
                $batch->addFailure($id, Lang::get('not_found', [], 'comments'));

                continue;
            }

            try {
                $comment->setStatus($status);
                $this->commentsRepository->update($comment);
                $batch->addSuccess($id);
            } catch (FlatFileException $e) {
                $batch->addFailure($id, $e->getMessage());
            }
        }

        return $this->json->success(
            $response,
            $batch->toArray(),
            200,
            Lang::get('bulk_updated', [], 'comments')
        );
    }

    public function bulkDelete(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $ids = $this->normalizeIds(
            (json_decode((string) $request->getBody(), true) ?: [])['ids'] ?? null
        );

        if ($ids === []) {
            return $this->json->error($response, Lang::get('ids_required', [], 'comments'), 400);
        }

        $batch = new BulkBatchResult();
        foreach ($ids as $id) {
            try {
                $this->commentsRepository->delete($id);
                $batch->addSuccess($id);
            } catch (FlatFileException $e) {
                $batch->addFailure($id, Lang::get('not_found', [], 'comments'));
            }
        }

        return $this->json->success(
            $response,
            $batch->toArray(),
            200,
            Lang::get('bulk_deleted', [], 'comments')
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
            array_map(static fn ($id): string => is_string($id) ? trim($id) : '', $value),
            static fn (string $id): bool => $id !== ''
        ));
    }

    /**
     * @return array<int|string, mixed>
     */
    private function publicShape(Comment $comment): array
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
}
