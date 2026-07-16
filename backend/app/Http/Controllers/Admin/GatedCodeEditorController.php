<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\CodeEditor\Services\CodeEditorManager;
use PaginiumCMS\Core\Developer\DeveloperModeGate;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use PaginiumCMS\Support\JsonHelper;

/**
 * Code editor s povinným odomknutým Developer Mode.
 * Registrované v DI ako CodeEditorController::class.
 */
class GatedCodeEditorController extends CodeEditorController
{
    public function __construct(
        CodeEditorManager $editor,
        private DeveloperModeGate $gate
    ) {
        parent::__construct($editor);
    }

    public function listFiles(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($denied = $this->gateDenied($response)) {
            return $denied;
        }

        return parent::listFiles($request, $response);
    }

    public function getFile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($denied = $this->gateDenied($response)) {
            return $denied;
        }

        return parent::getFile($request, $response);
    }

    public function saveFile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($denied = $this->gateDenied($response)) {
            return $denied;
        }

        return parent::saveFile($request, $response);
    }

    public function getBackups(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($denied = $this->gateDenied($response)) {
            return $denied;
        }

        return parent::getBackups($request, $response);
    }

    private function gateDenied(ResponseInterface $response): ?ResponseInterface
    {
        if (!$this->gate->isFeatureAvailable()) {
            $response = new Response();
            $response->getBody()->write(JsonHelper::encode([
                'success' => false,
                'error' => 'Developer Mode nie je povolený v konfigurácii (DEVELOPER_MODE / APP_DEBUG)',
            ], JSON_UNESCAPED_UNICODE));

            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        if (!$this->gate->isUnlocked()) {
            $response = new Response();
            $response->getBody()->write(JsonHelper::encode([
                'success' => false,
                'error' => 'Developer Mode je zamknutý. Odomknite cez TOTP alebo dev token.',
                'gate' => $this->gate->getStatus(),
            ], JSON_UNESCAPED_UNICODE));

            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        return null;
    }
}
