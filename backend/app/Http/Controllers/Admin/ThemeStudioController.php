<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Http\Themes\Exceptions\ThemeStudioException;
use PaginiumCMS\Http\Themes\Services\ThemeStudioPreviewService;
use PaginiumCMS\Http\Themes\Services\ThemeStudioService;
use PaginiumCMS\Http\Themes\Services\ThemeStudioValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Theme Studio file API (It.88a), validate (It.88b), sandboxed preview (It.88d). Persist is 88g.
 */
final class ThemeStudioController
{
    public function __construct(
        private ThemeStudioService $studio,
        private ThemeStudioValidator $validator,
        private ThemeStudioPreviewService $preview,
        private JsonResponder $json,
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function listFiles(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        unset($request);
        $id = (string) ($args['id'] ?? '');

        try {
            $files = $this->studio->listFiles($id);
        } catch (ThemeStudioException $exception) {
            return $this->json->error($response, $exception->getMessage(), $exception->httpStatus());
        }

        return $this->json->success($response, [
            'themeId' => $id,
            'files' => $files,
        ]);
    }

    /**
     * @param array<string, string> $args
     */
    public function getFile(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');
        $params = $request->getQueryParams();
        $path = isset($params['path']) && is_string($params['path']) ? $params['path'] : '';

        try {
            $file = $this->studio->readFile($id, $path);
        } catch (ThemeStudioException $exception) {
            return $this->json->error($response, $exception->getMessage(), $exception->httpStatus());
        }

        return $this->json->success($response, $file);
    }

    public function validate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if ($data === null) {
            return $this->json->error($response, 'Invalid JSON body', 400);
        }

        $themeId = isset($data['themeId']) && is_string($data['themeId']) ? $data['themeId'] : '';
        $relativePath = '';
        if (isset($data['relativePath']) && is_string($data['relativePath'])) {
            $relativePath = $data['relativePath'];
        } elseif (isset($data['path']) && is_string($data['path'])) {
            $relativePath = $data['path'];
        }
        $content = isset($data['content']) && is_string($data['content']) ? $data['content'] : '';

        if ($relativePath === '') {
            return $this->json->error($response, 'relativePath is required', 400);
        }

        try {
            $result = $this->validator->validate($themeId, $relativePath, $content);
        } catch (ThemeStudioException $exception) {
            return $this->json->error($response, $exception->getMessage(), $exception->httpStatus());
        }

        if ($result['valid']) {
            return $this->json->success($response, $result);
        }

        return $this->json->respond($response, [
            'success' => false,
            'error' => 'Theme policy validation failed',
            'data' => $result,
        ], 422);
    }

    public function preview(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if ($data === null) {
            return $this->json->error($response, 'Invalid JSON body', 400);
        }

        $themeId = isset($data['themeId']) && is_string($data['themeId']) ? $data['themeId'] : '';
        $template = isset($data['template']) && is_string($data['template']) ? $data['template'] : '';
        $files = $data['files'] ?? null;
        if (!is_array($files)) {
            return $this->json->error($response, 'files is required', 400);
        }

        try {
            $result = $this->preview->preview($themeId, $files, $template);
        } catch (ThemeStudioException $exception) {
            return $this->json->error($response, $exception->getMessage(), $exception->httpStatus());
        }

        if ($result['blocked']) {
            return $this->json->respond($response, [
                'success' => false,
                'error' => 'Theme preview blocked by policy',
                'data' => $result,
            ], 422);
        }

        return $this->json->success($response, $result);
    }
}
