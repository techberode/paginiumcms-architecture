<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Playground\PlaygroundPackGitImporter;
use PaginiumCMS\Modules\Playground\PlaygroundPackRegistry;
use PaginiumCMS\Modules\Playground\PlaygroundSettings;
use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Playground config + allow-listed pack assets + Git import (It.95). SUPER_ADMIN only.
 */
final class PlaygroundController
{
    public function __construct(
        private PlaygroundSettings $settings,
        private PlaygroundPackRegistry $registry,
        private PlaygroundPackGitImporter $importer,
        private JsonResponder $json,
    ) {
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        unset($request);

        return $this->json->success($response, [
            'enabled' => $this->settings->isEnabled(),
            'demoBlocked' => $this->settings->isDemoBlocked(),
            'defaultTemplate' => $this->settings->defaultTemplate(),
            'templates' => PlaygroundSettings::TEMPLATES,
            'packs' => $this->registry->list($this->settings->isEnabled()),
            'gitConfigured' => $this->settings->gitConfigured(),
            'gitRepoUrl' => $this->settings->gitRepoUrl(),
            'gitRef' => $this->settings->gitRef(),
        ]);
    }

    /**
     * @param array<string, string> $args
     */
    public function asset(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        unset($request);
        if ($this->settings->isDemoBlocked()) {
            return $this->json->error($response, 'playground_demo', 403);
        }
        if (!$this->settings->isEnabled()) {
            return $this->json->error($response, 'playground_disabled', 403);
        }

        $packId = (string) ($args['packId'] ?? '');
        $path = (string) ($args['path'] ?? '');
        $file = $this->registry->readAsset($packId, $path);
        if ($file === null) {
            return $this->json->error($response, 'playground_asset_not_found', 404);
        }

        return $this->json->success($response, $file);
    }

    public function importGit(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        unset($request);
        if ($this->settings->isDemoBlocked()) {
            return $this->json->error($response, 'playground_demo', 403);
        }

        try {
            $result = $this->importer->importFromSettings();
        } catch (CodePolicyViolationException $exception) {
            return $this->json->error($response, 'playground_import_blocked', 422, $exception->getErrors());
        } catch (RuntimeException $exception) {
            $message = $exception->getMessage();
            $status = $message === 'playground_demo' ? 403 : 400;

            return $this->json->error($response, $message, $status);
        }

        return $this->json->success($response, $result);
    }
}
