<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\CodeEditor\Services\CodeEditorManager;
use PaginiumCMS\Core\Developer\DeveloperModeGate;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

/**
 * Code editor s povinným odomknutým Developer Mode.
 * Registrované v DI ako CodeEditorController::class.
 */
class GatedCodeEditorController extends CodeEditorController
{
    public function __construct(
        CodeEditorManager $editor,
        private DeveloperModeGate $gate,
        JsonResponder $json
    ) {
        parent::__construct($editor, $json);
    }

    public function listFiles(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($denied = $this->gateDenied()) {
            return $denied;
        }

        return parent::listFiles($request, $response);
    }

    public function listDirectories(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($denied = $this->gateDenied()) {
            return $denied;
        }

        return parent::listDirectories($request, $response);
    }

    public function getFile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($denied = $this->gateDenied()) {
            return $denied;
        }

        return parent::getFile($request, $response);
    }

    public function saveFile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($denied = $this->gateDenied()) {
            return $denied;
        }

        return parent::saveFile($request, $response);
    }

    public function getBackups(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($denied = $this->gateDenied()) {
            return $denied;
        }

        return parent::getBackups($request, $response);
    }

    private function gateDenied(): ?ResponseInterface
    {
        if (!$this->gate->isFeatureAvailable()) {
            return $this->json->error(
                new Response(),
                'Developer Mode nie je povolený v konfigurácii (DEVELOPER_MODE / APP_DEBUG)',
                403
            );
        }

        if (!$this->gate->isUnlocked()) {
            return $this->json->respond(new Response(), [
                'success' => false,
                'error' => 'Developer Mode je zamknutý. Odomknite cez TOTP alebo dev token.',
                'gate' => $this->gate->getStatus(),
            ], 403);
        }

        return null;
    }
}
