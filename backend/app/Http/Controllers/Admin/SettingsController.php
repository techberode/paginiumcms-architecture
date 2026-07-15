<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use InvalidArgumentException;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Settings\SettingsSchema;
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
    public function __construct(private SettingsRepositoryInterface $settings)
    {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, [
            'success' => true,
            'data' => [
                'schema' => SettingsSchema::groups(),
                'values' => $this->maskSensitiveValues($this->settings->all()),
            ],
        ]);
    }

    /**
     * @param array<string, string> $args
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $group = (string) ($args['group'] ?? '');

        if (!SettingsSchema::hasGroup($group)) {
            return $this->json($response, ['success' => false, 'error' => 'Neznáma skupina nastavení'], 404);
        }

        return $this->json($response, [
            'success' => true,
            'data' => [
                'schema' => SettingsSchema::groups()[$group],
                'values' => $this->maskSensitiveValues([$group => $this->settings->group($group)])[$group] ?? [],
            ],
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
            // ValidationException zámerne prebubláva do jednotného Error Handlera (422).
            $values = $this->settings->setGroup($group, $payload);
        } catch (InvalidArgumentException $e) {
            return $this->json($response, ['success' => false, 'error' => $e->getMessage()], 404);
        }

        return $this->json($response, [
            'success' => true,
            'data' => ['values' => $values],
            'message' => 'Nastavenia uložené',
        ]);
    }

    /**
     * Verejný výrez efektívnych nastavení (bez citlivých údajov).
     * Dostupné pre všetkých prihlásených používateľov – editor, auto-save interval atď.
     */
    public function publicSettings(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $all = $this->settings->all();

        return $this->json($response, [
            'success' => true,
            'data' => [
                'general' => [
                    'siteName' => $all['general']['siteName'] ?? 'PaginiumCMS',
                    'language' => $all['general']['language'] ?? 'sk',
                    'maintenanceMode' => (bool) ($all['general']['maintenanceMode'] ?? false),
                ],
                'content' => $all['content'] ?? [],
                'editor' => $all['editor'] ?? [],
                'notifications' => [
                    'toastEnabled' => (bool) ($all['notifications']['toastEnabled'] ?? true),
                    'toastPosition' => (string) ($all['notifications']['toastPosition'] ?? 'top-right'),
                    'toastDuration' => (int) ($all['notifications']['toastDuration'] ?? 3000),
                    'toastDebugMode' => (bool) ($all['notifications']['toastDebugMode'] ?? false),
                ],
            ],
        ]);
    }

    /**
     * Reset na predvolené hodnoty (zahodí uložené odchýlky).
     */
    public function reset(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->settings->reset();

        return $this->json($response, [
            'success' => true,
            'data' => ['values' => $this->settings->all()],
            'message' => 'Nastavenia obnovené na predvolené hodnoty',
        ]);
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
            if (($field['type'] ?? '') === 'password' && ($payload[$field['key']] ?? '') === '********') {
                unset($payload[$field['key']]);
            }
        }

        return $payload;
    }

    /**
     * Mask password-type fields in API responses (values are still stored on save).
     *
     * @param array<string, array<string, mixed>> $values
     * @return array<string, array<string, mixed>>
     */
    private function maskSensitiveValues(array $values): array
    {
        foreach (SettingsSchema::groups() as $groupKey => $group) {
            if (!isset($values[$groupKey])) {
                continue;
            }
            foreach ($group['fields'] as $field) {
                if (($field['type'] ?? '') === 'password' && ($values[$groupKey][$field['key']] ?? '') !== '') {
                    $values[$groupKey][$field['key']] = '********';
                }
            }
        }

        return $values;
    }

    // === Blok: Pomocné metódy ===

    /**
     * @return array<string, mixed>
     */
    private function parseJsonBody(ServerRequestInterface $request): array
    {
        $data = json_decode((string) $request->getBody(), true);

        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function json(ResponseInterface $response, array $payload, int $status = 200): ResponseInterface
    {
        $response->getBody()->write((string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
