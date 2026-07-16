<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Media;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use PaginiumCMS\Support\JsonHelper;

class MediaController
{
    public function __construct(
        private MediaRepositoryInterface $mediaRepository
    ) {
    }

    public function listMedia(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $filters = [];

        if (!empty($params['type'])) {
            $filters['type'] = $params['type'];
        }

        if (!empty($params['mimeType'])) {
            $filters['mimeType'] = $params['mimeType'];
        }

        $files = array_map(
            fn ($file) => $file->jsonSerialize(),
            $this->mediaRepository->findAll($filters)
        );

        return $this->jsonSuccess($response, $files);
    }

    public function uploadMedia(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $uploadedFiles = $request->getUploadedFiles();
        $file = $uploadedFiles['file'] ?? null;

        if (!$file instanceof UploadedFileInterface || $file->getError() !== UPLOAD_ERR_OK) {
            return $this->jsonError($response, Lang::get('file_required', [], 'media'), 400);
        }

        $parsedBody = $request->getParsedBody();
        $altText = is_array($parsedBody) ? (string) ($parsedBody['altText'] ?? '') : '';

        try {
            $media = $this->mediaRepository->saveUpload(
                $file->getClientFilename() ?? 'upload.bin',
                (string) $file->getStream(),
                $file->getClientMediaType() ?? 'application/octet-stream',
                $altText
            );

            return $this->jsonSuccess($response, $media->jsonSerialize(), null, 201);
        } catch (FlatFileException $e) {
            return $this->jsonError($response, $e->getMessage(), 400);
        }
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function updateMedia(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $path = urldecode($args['path'] ?? '');
        $media = $this->mediaRepository->findByPath($path);

        if ($media === null) {
            return $this->jsonError($response, Lang::get('not_found', [], 'media'), 404);
        }

        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            return $this->jsonError($response, Lang::get('updated', [], 'media'), 400);
        }

        if (array_key_exists('altText', $data)) {
            $media->setAltText((string) $data['altText']);
        }

        try {
            $this->mediaRepository->update($media);

            return $this->jsonSuccess($response, $media->jsonSerialize(), Lang::get('updated', [], 'media'));
        } catch (FlatFileException $e) {
            return $this->jsonError($response, $e->getMessage(), 500);
        }
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function deleteMedia(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $path = urldecode($args['path'] ?? '');

        try {
            $this->mediaRepository->delete($path);

            return $this->jsonSuccess($response, null, Lang::get('deleted', [], 'media'));
        } catch (FlatFileException $e) {
            return $this->jsonError($response, Lang::get('not_found', [], 'media'), 404);
        }
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
}
