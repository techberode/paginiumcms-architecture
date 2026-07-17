<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Media;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

class MediaController
{
    public function __construct(
        private MediaRepositoryInterface $mediaRepository,
        private JsonResponder $json
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

        return $this->json->success($response, $files);
    }

    public function uploadMedia(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $uploadedFiles = $request->getUploadedFiles();
        $file = $uploadedFiles['file'] ?? null;

        if (!$file instanceof UploadedFileInterface || $file->getError() !== UPLOAD_ERR_OK) {
            return $this->json->error($response, Lang::get('file_required', [], 'media'), 400);
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

            return $this->json->success($response, $media->jsonSerialize(), 201);
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 400);
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
            return $this->json->error($response, Lang::get('not_found', [], 'media'), 404);
        }

        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            return $this->json->error($response, Lang::get('updated', [], 'media'), 400);
        }

        if (array_key_exists('altText', $data)) {
            $media->setAltText((string) $data['altText']);
        }

        try {
            $this->mediaRepository->update($media);

            return $this->json->success($response, $media->jsonSerialize(), 200, Lang::get('updated', [], 'media'));
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 500);
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

            return $this->json->success($response, null, 200, Lang::get('deleted', [], 'media'));
        } catch (FlatFileException $e) {
            return $this->json->error($response, Lang::get('not_found', [], 'media'), 404);
        }
    }
}
