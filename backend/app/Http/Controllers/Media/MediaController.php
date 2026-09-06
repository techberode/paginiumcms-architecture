<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Media;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PaginiumCMS\Modules\Media\Services\StockImageCatalog;
use PaginiumCMS\Modules\Media\Services\StockImageImporter;
use PaginiumCMS\Modules\Security\Exception\AuthorizationException;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\ContentPathAclGuard;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

class MediaController
{
    public function __construct(
        private MediaRepositoryInterface $mediaRepository,
        private FileReaderInterface $fileReader,
        private StockImageCatalog $stockImageCatalog,
        private StockImageImporter $stockImageImporter,
        private JsonResponder $json,
        private ContentPathAclGuard $pathAcl
    ) {
    }

    public function listFormats(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->mediaRepository->formatsPayload());
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function serveFile(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $path = urldecode((string) ($args['path'] ?? ''));
        if ($path === '' || str_contains($path, '..')) {
            return $response->withStatus(404);
        }

        $media = $this->mediaRepository->findByPath($path);
        if ($media === null || !$this->fileReader->exists($path)) {
            return $response->withStatus(404);
        }

        try {
            $binary = $this->fileReader->readBinary($path);
        } catch (FlatFileException) {
            return $response->withStatus(404);
        }

        $response->getBody()->write($binary);

        $mimeType = $media->getMimeType();

        // Anti-XSS: SVG (a akýkoľvek ne-rasterový/aktívny obsah) sa nikdy neservíruje
        // inline v same-origin kontexte, inak by vložený <script> spustil stored XSS.
        // Vynútime stiahnutie + CSP sandbox + nosniff.
        $isActiveMime = $mimeType === 'image/svg+xml'
            || str_contains($mimeType, 'html')
            || str_contains($mimeType, 'xml');

        $disposition = $isActiveMime ? 'attachment' : 'inline';

        $response = $response
            ->withHeader('Content-Type', $mimeType)
            ->withHeader('Content-Length', (string) strlen($binary))
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Cache-Control', 'private, max-age=3600')
            ->withHeader('Content-Disposition', $disposition . '; filename="' . addslashes($media->getFileName()) . '"');

        if ($isActiveMime) {
            $response = $response->withHeader('Content-Security-Policy', 'sandbox; default-src \'none\'');
        }

        return $response;
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

        if (array_key_exists('folder', $params)) {
            $filters['folder'] = (string) $params['folder'];
        }

        $files = array_map(
            fn ($file) => $file->jsonSerialize(),
            $this->mediaRepository->findAll($filters)
        );

        return $this->json->success($response, $files);
    }

    public function listFolders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->mediaRepository->listFolders());
    }

    public function listStockTopics(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            return $this->json->success($response, $this->stockImageCatalog->topics());
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    public function importStockImage(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            $data = [];
        }

        $topic = trim((string) ($data['topic'] ?? ''));
        $folder = trim((string) ($data['folder'] ?? ''));

        try {
            $this->pathAcl->requireAccess(
                $this->resolveUser($request),
                $this->pathAcl->mediaFolderPath($folder),
                'media:upload'
            );
            $media = $this->stockImageImporter->import($topic, $folder);

            return $this->json->success(
                $response,
                $media->jsonSerialize(),
                201,
                Lang::get('stock_imported', [], 'media')
            );
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        } catch (AuthorizationException $e) {
            return $this->json->error($response, $e->getMessage(), 403);
        }
    }

    public function createFolder(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, Lang::get('folder_required', [], 'media'), 400);
        }

        $folder = trim((string) ($data['folder'] ?? ''));
        if ($folder === '') {
            return $this->json->error($response, Lang::get('folder_required', [], 'media'), 400);
        }

        try {
            $this->pathAcl->requireAccess(
                $this->resolveUser($request),
                $this->pathAcl->mediaFolderPath($folder),
                'media:upload'
            );
            $this->mediaRepository->createFolder($folder);

            return $this->json->success(
                $response,
                ['folder' => $folder],
                201,
                Lang::get('folder_created', [], 'media')
            );
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        } catch (AuthorizationException $e) {
            return $this->json->error($response, $e->getMessage(), 403);
        }
    }

    public function bulkDeleteMedia(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data) || !isset($data['paths']) || !is_array($data['paths'])) {
            return $this->json->error($response, Lang::get('paths_required', [], 'media'), 400);
        }

        $paths = array_values(array_filter(
            array_map(static fn ($path): string => is_string($path) ? $path : '', $data['paths']),
            static fn (string $path): bool => $path !== ''
        ));

        if ($paths === []) {
            return $this->json->error($response, Lang::get('paths_required', [], 'media'), 400);
        }

        foreach ($paths as $path) {
            try {
                $this->pathAcl->requireAccess($this->resolveUser($request), $path, 'media:delete');
            } catch (AuthorizationException $e) {
                return $this->json->error($response, $e->getMessage(), 403);
            }
        }

        $deleted = $this->mediaRepository->bulkDelete($paths);

        return $this->json->success(
            $response,
            ['deleted' => $deleted],
            200,
            Lang::get('bulk_deleted', [], 'media')
        );
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
        $folder = is_array($parsedBody) ? (string) ($parsedBody['folder'] ?? '') : '';

        try {
            $this->pathAcl->requireAccess(
                $this->resolveUser($request),
                $this->pathAcl->mediaFolderPath($folder),
                'media:upload'
            );
            $media = $this->mediaRepository->saveUpload(
                $file->getClientFilename() ?? 'upload.bin',
                (string) $file->getStream(),
                $file->getClientMediaType() ?? 'application/octet-stream',
                $altText,
                $folder
            );

            return $this->json->success($response, $media->jsonSerialize(), 201);
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        } catch (AuthorizationException $e) {
            return $this->json->error($response, $e->getMessage(), 403);
        }
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function imageInfo(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $path = urldecode((string) ($args['path'] ?? ''));
        $media = $this->mediaRepository->findByPath($path);

        if ($media === null) {
            return $this->json->error($response, Lang::get('not_found', [], 'media'), 404);
        }

        try {
            $this->pathAcl->requireAccess($this->resolveUser($request), $path, 'media:upload');
            $info = $this->mediaRepository->inspectRaster($path);

            return $this->json->success($response, $info);
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        } catch (AuthorizationException $e) {
            return $this->json->error($response, $e->getMessage(), 403);
        }
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function serveOptimizePreview(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $user = $this->resolveUser($request);
        if ($user === null) {
            return $response->withStatus(401);
        }

        $token = (string) ($args['token'] ?? '');
        $preview = $this->mediaRepository->readOptimizePreview($token, $user->getId());
        if ($preview === null) {
            return $response->withStatus(404);
        }

        try {
            $this->pathAcl->requireAccess($user, $preview['mediaPath'], 'media:upload');
        } catch (AuthorizationException) {
            return $response->withStatus(403);
        }

        $binary = $preview['binary'];
        $response->getBody()->write($binary);

        return $response
            ->withHeader('Content-Type', $preview['mimeType'])
            ->withHeader('Content-Length', (string) strlen($binary))
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Cache-Control', 'private, no-store, max-age=0');
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function previewOptimizeMedia(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $path = urldecode((string) ($args['path'] ?? ''));
        $media = $this->mediaRepository->findByPath($path);

        if ($media === null) {
            return $this->json->error($response, Lang::get('not_found', [], 'media'), 404);
        }

        $user = $this->resolveUser($request);
        if ($user === null) {
            return $this->json->error($response, Lang::get('not_found', [], 'media'), 401);
        }

        [$targetWidth, $targetHeight] = $this->parseOptimizeTargets($request);

        try {
            $this->pathAcl->requireAccess($user, $path, 'media:upload');
            $result = $this->mediaRepository->previewOptimizeRaster(
                $path,
                $user->getId(),
                $targetWidth,
                $targetHeight
            );

            return $this->json->success($response, $result);
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        } catch (AuthorizationException $e) {
            return $this->json->error($response, $e->getMessage(), 403);
        }
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function applyOptimizeMedia(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $path = urldecode((string) ($args['path'] ?? ''));
        $media = $this->mediaRepository->findByPath($path);

        if ($media === null) {
            return $this->json->error($response, Lang::get('not_found', [], 'media'), 404);
        }

        $user = $this->resolveUser($request);
        if ($user === null) {
            return $this->json->error($response, Lang::get('not_found', [], 'media'), 401);
        }

        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, Lang::get('optimize_preview_expired', [], 'media'), 400);
        }

        $previewToken = trim((string) ($data['previewToken'] ?? ''));
        if ($previewToken === '') {
            return $this->json->error($response, Lang::get('optimize_preview_expired', [], 'media'), 400);
        }

        try {
            $this->pathAcl->requireAccess($user, $path, 'media:upload');
            $result = $this->mediaRepository->applyOptimizePreview($path, $previewToken, $user->getId());

            return $this->json->success($response, $result, 200, Lang::get('optimized', [], 'media'));
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        } catch (AuthorizationException $e) {
            return $this->json->error($response, $e->getMessage(), 403);
        }
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function optimizeMedia(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $path = urldecode((string) ($args['path'] ?? ''));
        $media = $this->mediaRepository->findByPath($path);

        if ($media === null) {
            return $this->json->error($response, Lang::get('not_found', [], 'media'), 404);
        }

        [$targetWidth, $targetHeight] = $this->parseOptimizeTargets($request);

        try {
            $this->pathAcl->requireAccess($this->resolveUser($request), $path, 'media:upload');
            $result = $this->mediaRepository->optimizeRaster($path, $targetWidth, $targetHeight);

            return $this->json->success($response, $result, 200, Lang::get('optimized', [], 'media'));
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        } catch (AuthorizationException $e) {
            return $this->json->error($response, $e->getMessage(), 403);
        }
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    private function parseOptimizeTargets(ServerRequestInterface $request): array
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return [null, null];
        }

        $targetWidth = isset($data['targetWidth']) ? (int) $data['targetWidth'] : null;
        $targetHeight = isset($data['targetHeight']) ? (int) $data['targetHeight'] : null;
        if ($targetWidth !== null && $targetWidth <= 0) {
            $targetWidth = null;
        }
        if ($targetHeight !== null && $targetHeight <= 0) {
            $targetHeight = null;
        }

        return [$targetWidth, $targetHeight];
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

        try {
            $this->pathAcl->requireAccess($this->resolveUser($request), $path, 'media:upload');
        } catch (AuthorizationException $e) {
            return $this->json->error($response, $e->getMessage(), 403);
        }

        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, Lang::get('updated', [], 'media'), 400);
        }

        if (array_key_exists('altText', $data)) {
            $media->setAltText((string) $data['altText']);
        }

        if (array_key_exists('title', $data)) {
            $media->setTitle((string) $data['title']);
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
            $this->pathAcl->requireAccess($this->resolveUser($request), $path, 'media:delete');
            $this->mediaRepository->delete($path);

            return $this->json->success($response, null, 200, Lang::get('deleted', [], 'media'));
        } catch (AuthorizationException $e) {
            return $this->json->error($response, $e->getMessage(), 403);
        } catch (FlatFileException $e) {
            return $this->json->error($response, Lang::get('not_found', [], 'media'), 404);
        }
    }

    private function resolveUser(ServerRequestInterface $request): ?User
    {
        $user = $request->getAttribute('user');

        return $user instanceof User ? $user : null;
    }
}
