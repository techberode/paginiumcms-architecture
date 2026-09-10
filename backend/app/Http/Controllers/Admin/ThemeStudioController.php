<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Themes\Exceptions\ThemeStudioException;
use PaginiumCMS\Http\Themes\Services\ThemeStudioService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Read-only Theme Studio file API (It.88a). Persist is 88g.
 */
final class ThemeStudioController
{
    public function __construct(
        private ThemeStudioService $studio,
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
}
