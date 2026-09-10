<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Http\Themes\Exceptions\ThemeStudioException;
use PaginiumCMS\Http\Themes\Services\ThemeStudioNormalizeService;
use PaginiumCMS\Http\Themes\Services\ThemeStudioPersistService;
use PaginiumCMS\Http\Themes\Services\ThemeStudioPreviewService;
use PaginiumCMS\Http\Themes\Services\ThemeStudioService;
use PaginiumCMS\Http\Themes\Services\ThemeStudioValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Psr7\Stream;

/**
 * Theme Studio file API (It.88a–g): read, validate, preview, normalize, persist, thumbnail.
 */
final class ThemeStudioController
{
    public function __construct(
        private ThemeStudioService $studio,
        private ThemeStudioValidator $validator,
        private ThemeStudioPreviewService $preview,
        private ThemeStudioNormalizeService $normalizer,
        private ThemeStudioPersistService $persist,
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
            'hasThumbnail' => $this->studio->hasPreviewPng($id),
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

    public function normalize(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if ($data === null) {
            return $this->json->error($response, 'Invalid JSON body', 400);
        }

        $themeId = isset($data['themeId']) && is_string($data['themeId']) ? $data['themeId'] : '';
        $html = isset($data['html']) && is_string($data['html']) ? $data['html'] : '';
        $css = isset($data['css']) && is_string($data['css']) ? $data['css'] : '';
        $js = isset($data['js']) && is_string($data['js']) ? $data['js'] : '';
        $files = $data['files'] ?? [];
        if (!is_array($files)) {
            return $this->json->error($response, 'files must be an object', 400);
        }

        try {
            $result = $this->normalizer->normalize($themeId, $files, $html, $css, $js);
        } catch (ThemeStudioException $exception) {
            return $this->json->error($response, $exception->getMessage(), $exception->httpStatus());
        }

        if ($result['rejected']) {
            return $this->json->respond($response, [
                'success' => false,
                'error' => 'Theme normalize rejected the import',
                'data' => $result,
            ], 422);
        }

        return $this->json->success($response, $result);
    }

    public function save(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if ($data === null) {
            return $this->json->error($response, 'Invalid JSON body', 400);
        }

        $themeId = isset($data['themeId']) && is_string($data['themeId']) ? $data['themeId'] : '';
        $files = $data['files'] ?? null;
        if (!is_array($files)) {
            return $this->json->error($response, 'files is required', 400);
        }

        try {
            $result = $this->persist->save($themeId, $files);
        } catch (ThemeStudioException $exception) {
            return $this->json->error($response, $exception->getMessage(), $exception->httpStatus());
        }

        if ($result['blocked']) {
            return $this->json->respond($response, [
                'success' => false,
                'error' => 'Theme save blocked by policy',
                'data' => $result,
            ], 422);
        }

        return $this->json->success($response, $result);
    }

    /**
     * @param array<string, string> $args
     */
    public function getThumbnail(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        unset($request);
        $id = (string) ($args['id'] ?? '');

        try {
            $bytes = $this->studio->readPreviewPng($id);
        } catch (ThemeStudioException $exception) {
            return $this->json->error($response, $exception->getMessage(), $exception->httpStatus());
        }

        $stream = fopen('php://temp', 'wb+');
        if ($stream === false) {
            return $this->json->error($response, 'Unable to read theme thumbnail.', 500);
        }
        fwrite($stream, $bytes);
        rewind($stream);

        return $response
            ->withStatus(200)
            ->withHeader('Content-Type', 'image/png')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Content-Disposition', 'inline; filename="preview.png"')
            ->withHeader('Cache-Control', 'private, no-store')
            ->withBody(new Stream($stream));
    }

    /**
     * @param array<string, string> $args
     */
    public function saveThumbnail(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');
        $file = $request->getUploadedFiles()['file'] ?? null;
        if (!$file instanceof UploadedFileInterface || $file->getError() !== UPLOAD_ERR_OK) {
            return $this->json->error($response, 'PNG file is required', 400);
        }

        $bytes = (string) $file->getStream()->getContents();

        try {
            $this->studio->writePreviewPng($id, $bytes);
        } catch (ThemeStudioException $exception) {
            return $this->json->error($response, $exception->getMessage(), $exception->httpStatus());
        }

        return $this->json->success($response, [
            'themeId' => $id,
            'hasThumbnail' => true,
        ]);
    }
}
