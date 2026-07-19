<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use InvalidArgumentException;
use PaginiumCMS\Core\Security\SecurityLogger;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Settings\SettingsSchema;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * === Controller: SettingsController (Admin) ===
 * Správa nastavení CMS (Iterácia 4). Schéma je riadená dátami, takže formulár
 * na frontende je generický a pridanie novej skupiny nevyžaduje zmenu FE ani BE.
 *
 *  - GET /api/admin/settings           : schéma + efektívne hodnoty (všetky skupiny)
 *  - GET /api/admin/settings/{group}   : schéma + hodnoty jednej skupiny
 *  - PUT /api/admin/settings/{group}   : validácia + uloženie skupiny
 *
 * Validačné chyby (ValidationException) rieši jednotný Error Handler → HTTP 422
 * s obalom `{ success:false, error, errors }`.
 */
final class SettingsController
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private JsonResponder $json,
        private SecurityLogger $securityLogger
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, [
            'schema' => SettingsSchema::groups(),
            'values' => $this->maskSensitiveValues($this->settings->all()),
        ]);
    }

    /**
     * @param array<string, string> $args
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $group = (string) ($args['group'] ?? '');

        if (!SettingsSchema::hasGroup($group)) {
            return $this->json->error($response, 'Neznáma skupina nastavení', 404);
        }

        return $this->json->success($response, [
            'schema' => SettingsSchema::groups()[$group],
            'values' => $this->maskSensitiveValues([$group => $this->settings->group($group)])[$group] ?? [],
        ]);
    }

    /**
     * @param array<string, string> $args
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $group = (string) ($args['group'] ?? '');
        $payload = $this->parseJsonBody($request);
        $payload = $this->stripMaskedSecrets($group, $payload);

        try {
            $values = $this->settings->setGroup($group, $payload);
        } catch (InvalidArgumentException $e) {
            return $this->json->error($response, $e->getMessage(), 404);
        }

        $user = $request->getAttribute('user');
        if ($user instanceof User) {
            $this->securityLogger->logSettingsChange($user, $group);
        }

        return $this->json->success($response, ['values' => $values], 200, 'Nastavenia uložené');
    }

    /**
     * Verejný výrez efektívnych nastavení (bez citlivých údajov).
     * Dostupné anonymne pre verejný web a prihlásených používateľov.
     */
    public function publicSettings(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $all = $this->settings->all();

        return $this->json->success($response, [
            'general' => [
                'siteName' => $all['general']['siteName'] ?? 'PaginiumCMS',
                'siteDescription' => (string) ($all['general']['siteDescription'] ?? ''),
                'language' => $all['general']['language'] ?? 'sk',
                'maintenanceMode' => (bool) ($all['general']['maintenanceMode'] ?? false),
                'allowRegistration' => (bool) ($all['general']['allowRegistration'] ?? true),
            ],
            'workflows' => [
                'registrationOtpEnabled' => (bool) ($all['workflows']['registrationOtpEnabled'] ?? false),
            ],
            'ui' => [
                'showListCounts' => (bool) ($all['ui']['showListCounts'] ?? true),
                'adminListPageSize' => (int) ($all['ui']['adminListPageSize'] ?? 20),
            ],
            'content' => $all['content'] ?? [],
            'editor' => $all['editor'] ?? [],
            'notifications' => [
                'toastEnabled' => (bool) ($all['notifications']['toastEnabled'] ?? true),
                'toastPosition' => (string) ($all['notifications']['toastPosition'] ?? 'top-right'),
                'toastDuration' => (int) ($all['notifications']['toastDuration'] ?? 3000),
                'toastDebugMode' => (bool) ($all['notifications']['toastDebugMode'] ?? false),
            ],
            'seo' => [
                'titleTemplate' => (string) ($all['seo']['titleTemplate'] ?? '%title% | %siteName%'),
                'defaultDescription' => (string) ($all['seo']['defaultDescription'] ?? ''),
                'defaultImage' => (string) ($all['seo']['defaultImage'] ?? ''),
                'robotsDefault' => (string) ($all['seo']['robotsDefault'] ?? 'index,follow'),
                'twitterCard' => (string) ($all['seo']['twitterCard'] ?? 'summary_large_image'),
            ],
            'feeds' => [
                'enabled' => (bool) ($all['feeds']['enabled'] ?? true),
            ],
            'sso' => [
                'enabled' => (bool) ($all['sso']['enabled'] ?? false),
            ],
            'demo' => [
                'enabled' => filter_var(getenv('DEMO_MODE') ?: ($_ENV['DEMO_MODE'] ?? false), FILTER_VALIDATE_BOOLEAN),
            ],
            'comments' => [
                'enabled' => (bool) ($all['comments']['enabled'] ?? true),
                'requireApproval' => (bool) ($all['comments']['requireApproval'] ?? true),
                'allowGuestComments' => (bool) ($all['comments']['allowGuestComments'] ?? true),
            ],
            'firewall' => [
                'enabled' => (bool) ($all['firewall']['enabled'] ?? true),
            ],
        ]);
    }

    /**
     * Reset na predvolené hodnoty (zahodí uložené odchýlky).
     */
    public function reset(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->settings->reset();

        return $this->json->success(
            $response,
            ['values' => $this->settings->all()],
            200,
            'Nastavenia obnovené na predvolené hodnoty'
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function stripMaskedSecrets(string $group, array $payload): array
    {
        if (!SettingsSchema::hasGroup($group)) {
            return $payload;
        }

        foreach (SettingsSchema::groups()[$group]['fields'] as $field) {
            if ($field['type'] === 'password' && ($payload[$field['key']] ?? '') === '********') {
                unset($payload[$field['key']]);
            }
        }

        return $payload;
    }

    /**
     * Mask password-type fields in API responses (values are still stored on save).
     *
     * @param array<string, array<int|string, mixed>> $values
     * @return array<string, array<int|string, mixed>>
     */
    private function maskSensitiveValues(array $values): array
    {
        foreach (SettingsSchema::groups() as $groupKey => $group) {
            if (!isset($values[$groupKey])) {
                continue;
            }
            foreach ($group['fields'] as $field) {
                if ($field['type'] === 'password' && ($values[$groupKey][$field['key']] ?? '') !== '') {
                    $values[$groupKey][$field['key']] = '********';
                }
            }
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonBody(ServerRequestInterface $request): array
    {
        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            return [];
        }

        $payload = [];
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $payload[$key] = $value;
            }
        }

        return $payload;
    }
}
