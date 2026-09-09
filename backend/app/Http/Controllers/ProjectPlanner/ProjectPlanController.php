<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\ProjectPlanner;

use PaginiumCMS\Core\AuditTrail\Services\AuditTrailService;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Exception\InvalidPathException;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Modules\ProjectPlanner\Contracts\ProjectPlanRepositoryInterface;
use PaginiumCMS\Modules\ProjectPlanner\Services\ProjectPlanApiPresenter;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Support\Lang;
use PaginiumCMS\Support\LogSanitizer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ProjectPlanController
{
    public function __construct(
        private ProjectPlanRepositoryInterface $repository,
        private ProjectPlanApiPresenter $presenter,
        private SettingsRepositoryInterface $settings,
        private AuditTrailService $auditTrail,
        private JsonResponder $json,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($disabled = $this->disabledResponse($response)) {
            return $disabled;
        }

        $plans = [];
        foreach ($this->repository->findAll() as $plan) {
            $plans[] = $this->presenter->summary($plan);
        }

        return $this->json->success($response, [
            'plans' => $plans,
            'count' => count($plans),
        ]);
    }

    public function overview(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($disabled = $this->disabledResponse($response)) {
            return $disabled;
        }

        return $this->json->success($response, $this->presenter->overview($this->repository->findAll()));
    }

    /**
     * @param array<string, string> $args
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if ($disabled = $this->disabledResponse($response)) {
            return $disabled;
        }

        try {
            $plan = $this->repository->findById((string) ($args['id'] ?? ''));
        } catch (InvalidPathException) {
            return $this->json->error($response, Lang::get('invalid_id', [], 'projectPlanner'), 400);
        }

        if ($plan === null) {
            return $this->json->error($response, Lang::get('not_found', [], 'projectPlanner'), 404);
        }

        return $this->json->success($response, $this->presenter->detail($plan));
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($disabled = $this->disabledResponse($response)) {
            return $disabled;
        }

        $payload = RequestJsonBody::decode($request);
        if ($payload === null) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'projectPlanner'), 400);
        }

        $user = $request->getAttribute('user');
        if ($user instanceof User && (!isset($payload['createdBy']) || $payload['createdBy'] === '')) {
            $payload['createdBy'] = $user->getId();
        }

        try {
            $plan = $this->repository->create($payload);
        } catch (InvalidPathException) {
            return $this->json->error($response, Lang::get('invalid_id', [], 'projectPlanner'), 400);
        } catch (ValidationException $exception) {
            return $this->json->validation($response, $exception->getMessage(), $exception->getErrors());
        } catch (FlatFileException $exception) {
            return $this->json->error($response, $exception->getMessage(), 500);
        }

        $this->audit($request, 'project_plan.create', $plan->id, $plan->title);

        return $this->json->success(
            $response,
            $this->presenter->detail($plan),
            201,
            Lang::get('created', [], 'projectPlanner')
        );
    }

    /**
     * @param array<string, string> $args
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if ($disabled = $this->disabledResponse($response)) {
            return $disabled;
        }

        $payload = RequestJsonBody::decode($request);
        if ($payload === null) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'projectPlanner'), 400);
        }

        try {
            $plan = $this->repository->update((string) ($args['id'] ?? ''), $payload);
        } catch (InvalidPathException) {
            return $this->json->error($response, Lang::get('invalid_id', [], 'projectPlanner'), 400);
        } catch (ValidationException $exception) {
            return $this->json->validation($response, $exception->getMessage(), $exception->getErrors());
        } catch (FlatFileException $exception) {
            return $this->json->error($response, $exception->getMessage(), 404);
        }

        $this->audit($request, 'project_plan.update', $plan->id, $plan->title);

        return $this->json->success(
            $response,
            $this->presenter->detail($plan),
            200,
            Lang::get('updated', [], 'projectPlanner')
        );
    }

    /**
     * @param array<string, string> $args
     */
    public function addItem(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if ($disabled = $this->disabledResponse($response)) {
            return $disabled;
        }

        $payload = RequestJsonBody::decode($request);
        if ($payload === null) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'projectPlanner'), 400);
        }

        try {
            $plan = $this->repository->addItem((string) ($args['id'] ?? ''), $payload);
        } catch (InvalidPathException) {
            return $this->json->error($response, Lang::get('invalid_id', [], 'projectPlanner'), 400);
        } catch (ValidationException $exception) {
            return $this->json->validation($response, $exception->getMessage(), $exception->getErrors());
        } catch (FlatFileException $exception) {
            $status = str_contains($exception->getMessage(), 'not found') ? 404 : 500;

            return $this->json->error($response, $exception->getMessage(), $status);
        }

        $this->audit($request, 'project_plan.item.create', $plan->id, $plan->title);

        return $this->json->success(
            $response,
            $this->presenter->detail($plan),
            201,
            Lang::get('item_created', [], 'projectPlanner')
        );
    }

    /**
     * @param array<string, string> $args
     */
    public function updateItem(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if ($disabled = $this->disabledResponse($response)) {
            return $disabled;
        }

        $payload = RequestJsonBody::decode($request);
        if ($payload === null) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'projectPlanner'), 400);
        }

        try {
            $plan = $this->repository->updateItem(
                (string) ($args['id'] ?? ''),
                (string) ($args['itemId'] ?? ''),
                $payload
            );
        } catch (InvalidPathException) {
            return $this->json->error($response, Lang::get('invalid_id', [], 'projectPlanner'), 400);
        } catch (ValidationException $exception) {
            return $this->json->validation($response, $exception->getMessage(), $exception->getErrors());
        } catch (FlatFileException $exception) {
            return $this->json->error($response, $exception->getMessage(), 404);
        }

        $this->audit($request, 'project_plan.item.update', $plan->id, $plan->title);

        return $this->json->success(
            $response,
            $this->presenter->detail($plan),
            200,
            Lang::get('item_updated', [], 'projectPlanner')
        );
    }

    /**
     * @param array<string, string> $args
     */
    public function deleteItem(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if ($disabled = $this->disabledResponse($response)) {
            return $disabled;
        }

        try {
            $plan = $this->repository->deleteItem(
                (string) ($args['id'] ?? ''),
                (string) ($args['itemId'] ?? '')
            );
        } catch (InvalidPathException) {
            return $this->json->error($response, Lang::get('invalid_id', [], 'projectPlanner'), 400);
        } catch (ValidationException $exception) {
            return $this->json->validation($response, $exception->getMessage(), $exception->getErrors());
        } catch (FlatFileException $exception) {
            return $this->json->error($response, $exception->getMessage(), 404);
        }

        $this->audit($request, 'project_plan.item.delete', $plan->id, $plan->title);

        return $this->json->success(
            $response,
            $this->presenter->detail($plan),
            200,
            Lang::get('item_deleted', [], 'projectPlanner')
        );
    }

    private function disabledResponse(ResponseInterface $response): ?ResponseInterface
    {
        $enabled = $this->settings->group('projectPlanner')['enabled'] ?? true;
        if ($enabled === false || $enabled === 0 || $enabled === '0') {
            return $this->json->error($response, Lang::get('disabled', [], 'projectPlanner'), 404);
        }

        return null;
    }

    private function audit(ServerRequestInterface $request, string $action, string $planId, string $title): void
    {
        $user = $request->getAttribute('user');
        $actor = $user instanceof User ? $user : null;
        $this->auditTrail->logAdminAction(
            $action,
            'project-plan:' . $planId,
            $actor,
            [
                'planId' => $planId,
                'title' => LogSanitizer::value($title, 200),
            ]
        );
    }
}
