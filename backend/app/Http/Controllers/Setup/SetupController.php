<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Setup;

use PaginiumCMS\Core\Setup\Services\FirstAdminBootstrapService;
use PaginiumCMS\Core\Setup\Services\SetupPreflightService;
use PaginiumCMS\Core\Setup\Services\SetupStatusService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Pre-auth setup wizard API (It.25).
 */
final class SetupController
{
    public function __construct(
        private SetupStatusService $setupStatus,
        private SetupPreflightService $preflight,
        private FirstAdminBootstrapService $firstAdmin,
        private SettingsRepositoryInterface $settings,
        private Validator $validator,
        private JsonResponder $json,
    ) {
    }

    public function preflight(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->preflight->run());
    }

    public function status(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, [
            'needsSetup' => $this->setupStatus->needsSetup(),
            'installed' => $this->setupStatus->isInstalled(),
            'hasUsers' => $this->firstAdmin->hasUsers(),
        ]);
    }

    public function complete(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$this->setupStatus->needsSetup()) {
            return $this->json->error($response, 'Setup already completed', 403);
        }

        $data = RequestJsonBody::decode($request);
        if ($data === null) {
            return $this->json->error($response, 'Invalid request body', 400);
        }

        try {
            $validated = $this->validator->validate($data, [
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:8', 'max:255'],
                'passwordConfirm' => ['required', 'string', 'min:8', 'max:255'],
                'name' => ['required', 'string', 'min:2', 'max:120'],
                'siteName' => ['required', 'string', 'min:2', 'max:120'],
                'language' => ['required', 'in:sk,en'],
                'backendPort' => ['nullable', 'string', 'max:8'],
                'storageDriver' => ['nullable', 'in:local'],
            ]);
        } catch (ValidationException $e) {
            return $this->json->validation($response, 'Validation failed', $e->getErrors());
        }

        if ((string) ($validated['password'] ?? '') !== (string) ($validated['passwordConfirm'] ?? '')) {
            return $this->json->validation($response, 'Validation failed', [
                'passwordConfirm' => ['Password confirmation does not match.'],
            ]);
        }

        $email = (string) $validated['email'];
        $password = (string) $validated['password'];
        $name = (string) $validated['name'];
        $siteName = (string) $validated['siteName'];
        $language = (string) $validated['language'];

        try {
            $this->firstAdmin->createFirstAdmin($email, $password, $name);
        } catch (\InvalidArgumentException $e) {
            return $this->json->error($response, $e->getMessage(), 409);
        } catch (\Throwable) {
            return $this->json->error($response, 'Could not create administrator account.', 500);
        }

        $this->settings->setGroup('general', [
            'siteName' => $siteName,
            'language' => $language,
            'adminEmail' => $email,
            'installed' => true,
            'allowRegistration' => false,
        ]);

        $backendPort = trim((string) ($validated['backendPort'] ?? ''));
        if ($backendPort !== '') {
            $this->settings->setGroup('systemUpdate', [
                'backendPort' => $backendPort,
            ]);
        }

        $storageDriver = trim((string) ($validated['storageDriver'] ?? ''));
        if ($storageDriver !== '') {
            $this->settings->setGroup('media', [
                'storageDriver' => $storageDriver,
            ]);
        }

        return $this->json->respond($response, [
            'success' => true,
            'installed' => true,
            'loginRequired' => true,
            'redirectTo' => '/login',
        ]);
    }
}
