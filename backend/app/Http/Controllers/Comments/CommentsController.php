<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Comments;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\Security\ClientIpResolver;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Core\Validation\VisitorEmailGuard;
use PaginiumCMS\Core\Workflow\Services\OtpWorkflowService;
use PaginiumCMS\Http\Support\BulkBatchResult;
use PaginiumCMS\Http\Support\BulkOperationLimits;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Modules\Comments\Contracts\CommentsRepositoryInterface;
use PaginiumCMS\Modules\Comments\Models\Comment;
use PaginiumCMS\Modules\Comments\Services\CommentPolicyResolver;
use PaginiumCMS\Modules\Messages\Services\DeskInboxService;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CommentsController
{
    public function __construct(
        private CommentsRepositoryInterface $commentsRepository,
        private SettingsRepositoryInterface $settingsRepository,
        private CommentPolicyResolver $commentPolicy,
        private Validator $validator,
        private OtpWorkflowService $otpWorkflow,
        private JsonResponder $json,
        private DeskInboxService $desk,
        private VisitorEmailGuard $visitorEmail
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

        $comments = [];
        foreach ($this->commentsRepository->findAll($filters) as $comment) {
            if ($comment->getParentId() !== '') {
                continue;
            }
            $comments[] = $this->desk->publicComment($comment);
        }

        return $this->json->success($response, $comments);
    }

    public function submit(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'comments'), 400);
        }

        if (trim((string) ($data['_hp'] ?? '')) !== '') {
            return $this->json->success(
                $response,
                [
                    'id' => 'hp_' . bin2hex(random_bytes(8)),
                    'articleSlug' => trim((string) ($data['articleSlug'] ?? '')),
                    'author' => (string) ($data['author'] ?? ''),
                    'content' => (string) ($data['content'] ?? ''),
                    'status' => Comment::STATUS_PENDING,
                    'createdAt' => date('c'),
                    'approvedAt' => null,
                ],
                201,
                Lang::get('submitted', [], 'comments')
            );
        }

        try {
            $validated = $this->validator->validate($data, [
                'articleSlug' => ['required', 'string', 'min:1', 'max:120'],
                'author' => ['required', 'string', 'min:2', 'max:120'],
                'email' => ['required', 'email', 'max:255'],
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

        $articleSlug = (string) $validated['articleSlug'];
        $policy = $this->commentPolicy->resolveForArticle($articleSlug);
        if (!$policy['enabled']) {
            return $this->json->error($response, Lang::get('disabled_for_article', [], 'comments'), 403);
        }

        $user = $request->getAttribute('user');
        if ($user === null && !$policy['allowGuestComments']) {
            return $this->json->error($response, 'Anonymné komentáre sú pre tento článok vypnuté', 403);
        }

        $clientIp = ClientIpResolver::resolve($request->getServerParams(), ClientIpResolver::trustedProxiesFromEnv());
        $spamVerdict = $this->commentPolicy->evaluateSubmission($data, $clientIp);

        if ($spamVerdict->isRejectSilent()) {
            return $this->json->success(
                $response,
                [
                    'id' => 'hp_' . bin2hex(random_bytes(8)),
                    'articleSlug' => $articleSlug,
                    'author' => (string) $validated['author'],
                    'content' => (string) $validated['content'],
                    'status' => Comment::STATUS_PENDING,
                    'createdAt' => date('c'),
                    'approvedAt' => null,
                ],
                201,
                Lang::get('submitted', [], 'comments')
            );
        }

        if ($spamVerdict->isReject()) {
            return $this->json->error($response, Lang::get('spam_rejected', [], 'comments'), 422);
        }

        $comment = new Comment(
            $articleSlug,
            (string) $validated['author'],
            (string) $validated['content']
        );
        try {
            $comment->setEmail($this->visitorEmail->normalize((string) ($validated['email'] ?? '')));
        } catch (ValidationException $e) {
            return $this->json->validation($response, Lang::get('validation_failed', [], 'comments'), $e->getErrors());
        }

        $rating = (int) ($data['rating'] ?? 0);
        if ($policy['ratingEnabled']) {
            if ($rating < 1 || $rating > 5) {
                return $this->json->validation(
                    $response,
                    Lang::get('validation_failed', [], 'comments'),
                    ['rating' => [Lang::get('rating_required', [], 'comments')]]
                );
            }
            $comment->setRating($rating);
        }

        if ($spamVerdict->isQuarantine()) {
            $comment->setStatus(Comment::STATUS_QUARANTINE);
        } elseif (!$policy['requireApproval']) {
            $comment->setStatus(Comment::STATUS_APPROVED);
        }

        $this->commentsRepository->save($comment);
        $this->commentPolicy->recordSubmission($clientIp);

        return $this->json->success(
            $response,
            $this->desk->publicComment($comment),
            201,
            Lang::get('submitted', [], 'comments')
        );
    }

    /**
     * @param array<string, string> $args
     */
    public function reply(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $actor = $request->getAttribute('user');
        if (!$actor instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }
        if (!$this->desk->canReplyComments($actor)) {
            return $this->json->error($response, Lang::get('forbidden', [], 'comments'), 403);
        }

        $parent = $this->commentsRepository->findById(trim((string) ($args['id'] ?? '')));
        if ($parent === null || $parent->getStatus() !== Comment::STATUS_APPROVED) {
            return $this->json->error($response, Lang::get('not_found', [], 'comments'), 404);
        }

        $data = RequestJsonBody::decode($request);
        $body = is_array($data) ? trim((string) ($data['content'] ?? $data['body'] ?? '')) : '';
        if (mb_strlen($body) < 2) {
            return $this->json->error($response, Lang::get('content_required', [], 'comments'), 400);
        }

        $reply = $this->desk->replyToComment($parent, $actor, $body);
        if ($reply === null) {
            return $this->json->error($response, Lang::get('claimed', [], 'comments'), 409);
        }

        return $this->json->success($response, $this->desk->publicComment($reply), 201, Lang::get('replied', [], 'comments'));
    }

    /**
     * @param array<string, string> $args
     */
    public function claim(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $actor = $request->getAttribute('user');
        if (!$actor instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }
        if (!$this->desk->canReplyComments($actor)) {
            return $this->json->error($response, Lang::get('forbidden', [], 'comments'), 403);
        }

        $comment = $this->commentsRepository->findById(trim((string) ($args['id'] ?? '')));
        if ($comment === null) {
            return $this->json->error($response, Lang::get('not_found', [], 'comments'), 404);
        }
        if (!$this->desk->claimComment($comment, $actor)) {
            return $this->json->error($response, Lang::get('claimed', [], 'comments'), 409);
        }

        return $this->json->success($response, $this->desk->publicComment($comment));
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

        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'comments'), 400);
        }

        if (isset($data['status'])) {
            $status = (string) $data['status'];
            if (!in_array($status, [Comment::STATUS_PENDING, Comment::STATUS_APPROVED, Comment::STATUS_REJECTED, Comment::STATUS_QUARANTINE], true)) {
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

        if (array_key_exists('isRead', $data)) {
            $comment->markRead((bool) $data['isRead']);
        }

        if (array_key_exists('isArchived', $data)) {
            $comment->markArchived((bool) $data['isArchived']);
        }

        if (array_key_exists('handleStatus', $data)) {
            $comment->setHandleStatus((string) $data['handleStatus']);
        }

        if (array_key_exists('isProcessed', $data)) {
            $comment->markProcessed((bool) $data['isProcessed']);
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
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'comments'), 400);
        }

        $ids = $this->normalizeIds($data['ids'] ?? null);
        $status = (string) ($data['status'] ?? '');

        if ($ids === []) {
            return $this->json->error($response, Lang::get('ids_required', [], 'comments'), 400);
        }

        $limitResponse = $this->rejectIfBulkLimitExceeded($ids, $response);
        if ($limitResponse !== null) {
            return $limitResponse;
        }

        if (!in_array($status, [Comment::STATUS_PENDING, Comment::STATUS_APPROVED, Comment::STATUS_REJECTED, Comment::STATUS_QUARANTINE], true)) {
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
        $payload = RequestJsonBody::decode($request);
        $ids = $this->normalizeIds(
            is_array($payload) ? ($payload['ids'] ?? null) : null
        );

        if ($ids === []) {
            return $this->json->error($response, Lang::get('ids_required', [], 'comments'), 400);
        }

        $limitResponse = $this->rejectIfBulkLimitExceeded($ids, $response);
        if ($limitResponse !== null) {
            return $limitResponse;
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

    public function bulkWorkflow(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'comments'), 400);
        }

        $ids = $this->normalizeIds($data['ids'] ?? null);
        $action = (string) ($data['action'] ?? '');

        if ($ids === []) {
            return $this->json->error($response, Lang::get('ids_required', [], 'comments'), 400);
        }

        $limitResponse = $this->rejectIfBulkLimitExceeded($ids, $response);
        if ($limitResponse !== null) {
            return $limitResponse;
        }

        if (!in_array($action, ['read', 'processed', 'approve', 'archive'], true)) {
            return $this->json->error($response, Lang::get('invalid_action', [], 'comments'), 422);
        }

        $batch = new BulkBatchResult();
        foreach ($ids as $id) {
            $comment = $this->commentsRepository->findById($id);
            if ($comment === null) {
                $batch->addFailure($id, Lang::get('not_found', [], 'comments'));

                continue;
            }

            try {
                if ($action === 'read') {
                    $comment->markRead(true);
                } elseif ($action === 'processed') {
                    if ($comment->getStatus() === Comment::STATUS_PENDING) {
                        $comment->setStatus(Comment::STATUS_APPROVED);
                    }
                    $comment->markProcessed(true);
                } elseif ($action === 'approve') {
                    $comment->setStatus(Comment::STATUS_APPROVED)->markRead(true);
                } elseif ($action === 'archive') {
                    $comment->markArchived(true)->markProcessed(true);
                }

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

    /**
     * @param mixed $value
     * @return list<string>
     */
    private function normalizeIds(mixed $value): array
    {
        return BulkOperationLimits::normalizeIds($value);
    }

    /**
     * @param list<string> $ids
     */
    private function rejectIfBulkLimitExceeded(array $ids, ResponseInterface $response): ?ResponseInterface
    {
        try {
            BulkOperationLimits::assertWithinLimit($ids);
        } catch (ValidationException $e) {
            return $this->json->validation($response, Lang::get('validation_failed', [], 'comments'), $e->getErrors());
        }

        return null;
    }
}
