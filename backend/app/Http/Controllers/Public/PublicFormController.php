<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Public;

use InvalidArgumentException;
use PaginiumCMS\Core\Forms\Services\PublicFormService;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PublicFormController
{
    public function __construct(
        private PublicFormService $forms,
        private JsonResponder $json,
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function schema(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');
        try {
            return $this->json->success($response, $this->forms->publicSchema($id));
        } catch (InvalidArgumentException $e) {
            return $this->json->error($response, $e->getMessage(), 404);
        }
    }

    /**
     * @param array<string, string> $args
     */
    public function submit(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'contact'), 400);
        }

        if (trim((string) ($data['_hp'] ?? '')) !== '') {
            return $this->json->success(
                $response,
                ['id' => 'hp_' . bin2hex(random_bytes(8))],
                201,
                Lang::get('submitted', [], 'contact')
            );
        }

        try {
            $result = $this->forms->submit(
                $id,
                $data,
                (string) ($request->getServerParams()['REMOTE_ADDR'] ?? 'unknown')
            );
        } catch (ValidationException $e) {
            return $this->json->validation(
                $response,
                Lang::get('validation_failed', [], 'contact'),
                $e->getErrors()
            );
        } catch (InvalidArgumentException $e) {
            return $this->json->error($response, $e->getMessage(), 404);
        }

        return $this->json->success(
            $response,
            ['id' => $result['message']->getId(), 'appended' => $result['appended']],
            201,
            Lang::get('submitted', [], 'contact')
        );
    }
}
