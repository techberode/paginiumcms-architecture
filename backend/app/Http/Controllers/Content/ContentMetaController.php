<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Content;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\Editor\Services\ContentBodyRenderer;
use PaginiumCMS\Core\FlatFile\Services\ContentMetaGenerator;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ContentMetaController
{
    private const MAX_BODY_BYTES = 512_000;

    public function __construct(
        private ContentMetaGenerator $generator,
        private ContentBodyRenderer $bodyRenderer,
        private SettingsRepositoryInterface $settings,
        private JsonResponder $json,
    ) {
    }

    public function suggestMeta(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = RequestJsonBody::decode($request);
        if (!is_array($payload)) {
            return $this->json->error($response, Lang::get('invalid_json', [], 'content'), 400);
        }

        $type = strtolower(trim((string) ($payload['type'] ?? '')));
        if (!in_array($type, ['page', 'article'], true)) {
            return $this->json->error($response, Lang::get('invalid_type', [], 'content'), 400);
        }

        $title = trim((string) ($payload['title'] ?? ''));
        $body = (string) ($payload['body'] ?? '');
        if (strlen($body) > self::MAX_BODY_BYTES) {
            return $this->json->error($response, Lang::get('body_too_large', [], 'content'), 413);
        }

        $bodyFormat = strtolower(trim((string) ($payload['bodyFormat'] ?? 'markdown')));
        if (!in_array($bodyFormat, ['markdown', 'html', 'tiptap_json'], true)) {
            return $this->json->error($response, Lang::get('invalid_body_format', [], 'content'), 400);
        }

        $contentSettings = $this->settings->group('content');
        $autoTagEnabled = (bool) ($contentSettings['autoTagEnabled'] ?? true);
        $autoDescriptionEnabled = (bool) ($contentSettings['autoDescriptionEnabled'] ?? true);
        $maxTags = (int) ($contentSettings['autoTagMax'] ?? 8);
        $maxDescription = (int) ($contentSettings['autoDescriptionMaxLength'] ?? 155);

        $existingTags = [];
        if (isset($payload['existingTags']) && is_array($payload['existingTags'])) {
            foreach ($payload['existingTags'] as $tag) {
                if (is_string($tag) && trim($tag) !== '') {
                    $existingTags[] = trim($tag);
                }
            }
        }

        $tags = [];
        if ($autoTagEnabled && $type === 'article') {
            $tags = $this->generator->suggestTags($title, $body, $bodyFormat, $maxTags, $existingTags);
        }

        $description = '';
        if ($autoDescriptionEnabled) {
            $description = $this->generator->suggestDescription($title, $body, $bodyFormat, $maxDescription);
        }

        return $this->json->success($response, [
            'tags' => $tags,
            'description' => $description,
        ]);
    }

    public function renderPreview(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = RequestJsonBody::decode($request);
        if (!is_array($payload)) {
            return $this->json->error($response, Lang::get('invalid_json', [], 'content'), 400);
        }

        $body = (string) ($payload['body'] ?? '');
        if (strlen($body) > self::MAX_BODY_BYTES) {
            return $this->json->error($response, Lang::get('body_too_large', [], 'content'), 413);
        }

        $bodyFormat = strtolower(trim((string) ($payload['bodyFormat'] ?? 'markdown')));
        if (!in_array($bodyFormat, ['markdown', 'html', 'tiptap_json'], true)) {
            return $this->json->error($response, Lang::get('invalid_body_format', [], 'content'), 400);
        }

        $cachedHtml = isset($payload['cachedHtml']) && is_string($payload['cachedHtml'])
            ? $payload['cachedHtml']
            : null;

        return $this->json->success($response, [
            'html' => $this->bodyRenderer->resolveHtml($body, $bodyFormat, $cachedHtml),
        ]);
    }
}
