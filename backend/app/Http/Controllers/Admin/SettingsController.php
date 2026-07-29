<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use InvalidArgumentException;
use PaginiumCMS\Core\I18n\Services\SupportedLocalesRegistry;
use PaginiumCMS\Core\Security\SecurityLogger;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Settings\SettingsSchema;
use PaginiumCMS\Core\Editor\Services\EditorComponentRegistry;
use PaginiumCMS\Core\Editor\Services\EditorProfileService;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Demo\Data\DemoFixtures;
use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Core\Settings\Services\SocialLinksNormalizer;
use PaginiumCMS\Modules\Security\PermissionCatalog;
use PaginiumCMS\Modules\Security\Services\AccessControlSyncService;
use PaginiumCMS\Support\AppVersion;
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
        private SecurityLogger $securityLogger,
        private DemoMode $demoMode,
        private EditorProfileService $editorProfiles,
        private EditorComponentRegistry $editorComponents,
        private AccessControlSyncService $accessControlSync,
        private AuthorizationInterface $authorization,
        private SupportedLocalesRegistry $localesRegistry
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        /** @var User|null $user */
        $user = $request->getAttribute('user');
        $schema = SettingsSchema::filterSchemaForUser(SettingsSchema::groups(), $user);
        $values = SettingsSchema::filterValuesForUser(
            $this->maskSensitiveValues($this->enrichAccessControlValues($this->settings->all())),
            $user
        );

        return $this->json->success($response, [
            'schema' => $schema,
            'values' => $values,
            'meta' => [
                'permissions' => PermissionCatalog::ALL,
                'configurableRoles' => PermissionCatalog::configurableRoles(),
                'cmsInfo' => $this->buildCmsInfoMeta(),
                'editorComponents' => $this->editorComponents->listRegisteredForApi(),
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
            return $this->json->error($response, 'Neznáma skupina nastavení', 404);
        }

        /** @var User|null $user */
        $user = $request->getAttribute('user');
        if (!SettingsSchema::userCanAccessGroup($user, $group)) {
            return $this->json->error($response, 'Nemáte oprávnenie na túto skupinu nastavení', 403);
        }

        if (SettingsSchema::isSuperAdminOnly($group) && !$this->isSuperAdmin($user)) {
            return $this->json->error($response, 'Len super administrátor môže zobraziť túto skupinu nastavení', 403);
        }

        $groupValues = $this->settings->group($group);
        if ($group === 'accessControl') {
            $groupValues = $this->mergeLegacyPathAcl($groupValues);
        }

        return $this->json->success($response, [
            'schema' => SettingsSchema::groups()[$group],
            'values' => $this->maskSensitiveValues([$group => $groupValues])[$group] ?? [],
            'meta' => $group === 'accessControl' ? [
                'permissions' => PermissionCatalog::ALL,
                'configurableRoles' => PermissionCatalog::configurableRoles(),
            ] : null,
        ]);
    }

    /**
     * @param array<string, string> $args
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $group = (string) ($args['group'] ?? '');

        if (!SettingsSchema::hasGroup($group)) {
            return $this->json->error($response, 'Neznáma skupina nastavení', 404);
        }

        /** @var User|null $user */
        $user = $request->getAttribute('user');
        if (!SettingsSchema::userCanAccessGroup($user, $group)) {
            return $this->json->error($response, 'Nemáte oprávnenie na túto skupinu nastavení', 403);
        }

        if (SettingsSchema::isSuperAdminOnly($group) && !$this->isSuperAdmin($user)) {
            return $this->json->error($response, 'Len super administrátor môže meniť túto skupinu nastavení', 403);
        }

        if (SettingsSchema::isInformational($group)) {
            return $this->json->error($response, 'Táto skupina nastavení je len na čítanie', 403);
        }

        $payload = $this->parseJsonBody($request);
        $payload = $this->stripMaskedSecrets($group, $payload);

        if ($group === 'accessControl') {
            try {
                $payload = $this->accessControlSync->normalizeAccessControlPayload($payload);
            } catch (InvalidArgumentException $e) {
                return $this->json->error($response, $e->getMessage(), 422);
            }
        }

        if ($group === 'marketing' && array_key_exists('socialLinksJson', $payload)) {
            try {
                $normalized = SocialLinksNormalizer::normalizeJson((string) $payload['socialLinksJson']);
                $payload['socialLinksJson'] = SocialLinksNormalizer::encode($normalized);
            } catch (InvalidArgumentException $e) {
                return $this->json->error($response, $e->getMessage(), 422);
            }
        }

        try {
            $values = $this->settings->setGroup($group, $payload);
        } catch (InvalidArgumentException $e) {
            return $this->json->error($response, $e->getMessage(), 404);
        }

        if ($group === 'accessControl') {
            try {
                $this->accessControlSync->syncPathAclFromSettings($values);
            } catch (InvalidArgumentException $e) {
                return $this->json->error($response, $e->getMessage(), 422);
            }

            if ($this->authorization instanceof \PaginiumCMS\Modules\Security\Services\AuthorizationManager) {
                $this->authorization->reloadFromSettings();
            }
        }

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
                'allowRegistration' => (bool) ($all['general']['allowRegistration'] ?? true),
            ],
            'branding' => [
                'logoUrl' => (string) ($all['branding']['logoUrl'] ?? ''),
                'faviconUrl' => (string) ($all['branding']['faviconUrl'] ?? ''),
            ],
            'appearance' => $this->publicAppearanceSettings($all['appearance'] ?? []),
            'maintenance' => $this->publicMaintenanceSettings($all['maintenance'] ?? []),
            'workflows' => [
                'registrationOtpEnabled' => (bool) ($all['workflows']['registrationOtpEnabled'] ?? false),
            ],
            'ui' => [
                'showListCounts' => (bool) ($all['ui']['showListCounts'] ?? true),
                'adminListPageSize' => (int) ($all['ui']['adminListPageSize'] ?? 20),
                'openLinksInNewTab' => (bool) ($all['ui']['openLinksInNewTab'] ?? false),
            ],
            'navigationUi' => [
                'defaultPreviewScale' => ((int) ($all['navigationUi']['defaultPreviewScale'] ?? 15)) / 10.0,
                'maxTooltipWidthPx' => (int) ($all['navigationUi']['maxTooltipWidthPx'] ?? 280),
                'enableHoverAnimations' => (bool) ($all['navigationUi']['enableHoverAnimations'] ?? true),
            ],
            'content' => $all['content'] ?? [],
            'editor' => array_merge($all['editor'] ?? [], [
                'profiles' => $this->editorProfiles->listProfilesForApi(),
                'customComponents' => $this->editorComponents->listRegisteredForApi(),
            ]),
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
            'login' => [
                'pageTitle' => (string) ($all['login']['pageTitle'] ?? ''),
                'pageDescription' => (string) ($all['login']['pageDescription'] ?? ''),
                'backgroundImageUrl' => (string) ($all['login']['backgroundImageUrl'] ?? ''),
                'infoBullets' => (string) ($all['login']['infoBullets'] ?? ''),
            ],
            'security' => [
                'passwordMinLength' => (int) ($all['security']['passwordMinLength'] ?? 8),
                'passwordMaxLength' => (int) ($all['security']['passwordMaxLength'] ?? 72),
                'passwordRequireUppercase' => (bool) ($all['security']['passwordRequireUppercase'] ?? true),
                'passwordRequireLowercase' => (bool) ($all['security']['passwordRequireLowercase'] ?? true),
                'passwordRequireNumbers' => (bool) ($all['security']['passwordRequireNumbers'] ?? true),
                'passwordRequireSpecialChars' => (bool) ($all['security']['passwordRequireSpecialChars'] ?? true),
            ],
            'demo' => $this->publicDemoSettings($all['marketing'] ?? []),
            'social' => $this->publicSocialSettings($all['marketing'] ?? []),
            'gallery' => $this->publicGallerySettings($all['gallery'] ?? []),
            'comments' => [
                'enabled' => (bool) ($all['comments']['enabled'] ?? true),
                'requireApproval' => (bool) ($all['comments']['requireApproval'] ?? true),
                'allowGuestComments' => (bool) ($all['comments']['allowGuestComments'] ?? true),
            ],
            'contact' => [
                'subjects' => (string) ($all['contact']['subjects'] ?? "Všeobecný dotaz\nTechnická podpora\nObchodná spolupráca\nInformácie o produkte"),
                'allowCustomSubject' => (bool) ($all['contact']['allowCustomSubject'] ?? true),
            ],
            'newsletter' => $this->publicNewsletterSettings($all['newsletter'] ?? []),
            'privacy' => $this->publicPrivacySettings($all['privacy'] ?? []),
            'company' => [
                'showOnContactPage' => (bool) ($all['company']['showOnContactPage'] ?? true),
                'name' => (string) ($all['company']['name'] ?? ''),
                'legalName' => (string) ($all['company']['legalName'] ?? ''),
                'ico' => (string) ($all['company']['ico'] ?? ''),
                'dic' => (string) ($all['company']['dic'] ?? ''),
                'icDph' => (string) ($all['company']['icDph'] ?? ''),
                'address' => (string) ($all['company']['address'] ?? ''),
                'email' => (string) ($all['company']['email'] ?? ''),
                'phone' => (string) ($all['company']['phone'] ?? ''),
                'website' => (string) ($all['company']['website'] ?? ''),
                'mapEmbedUrl' => (string) ($all['company']['mapEmbedUrl'] ?? ''),
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
     * @param array<string, mixed> $appearance
     * @return array{colorScheme: string, mode: string, allowUserToggle: bool}
     */
    private function publicAppearanceSettings(array $appearance): array
    {
        $defaults = SettingsSchema::defaults()['appearance'] ?? [];
        $allowedSchemes = ['indigo-classic', 'ocean-slate', 'forest-sage', 'sunset-rose', 'mono-zinc'];
        $allowedModes = ['light', 'dark', 'system'];

        $colorScheme = (string) ($appearance['colorScheme'] ?? $defaults['colorScheme'] ?? 'indigo-classic');
        if (!in_array($colorScheme, $allowedSchemes, true)) {
            $colorScheme = 'indigo-classic';
        }

        $mode = (string) ($appearance['mode'] ?? $defaults['mode'] ?? 'system');
        if (!in_array($mode, $allowedModes, true)) {
            $mode = 'system';
        }

        return [
            'colorScheme' => $colorScheme,
            'mode' => $mode,
            'allowUserToggle' => (bool) ($appearance['allowUserToggle'] ?? $defaults['allowUserToggle'] ?? true),
        ];
    }

    /**
     * @param array<string, mixed> $maintenance
     * @return array<string, mixed>
     */
    private function publicMaintenanceSettings(array $maintenance): array
    {
        $defaults = SettingsSchema::defaults()['maintenance'] ?? [];

        return [
            'mode' => (string) ($maintenance['mode'] ?? $defaults['mode'] ?? 'off'),
            'heroImageUrl' => (string) ($maintenance['heroImageUrl'] ?? ''),
            'newsletterEnabled' => (bool) ($maintenance['newsletterEnabled'] ?? true),
            'newsletterHint' => (string) ($maintenance['newsletterHint'] ?? $defaults['newsletterHint'] ?? ''),
            'comingSoonBadge' => (string) ($maintenance['comingSoonBadge'] ?? $defaults['comingSoonBadge'] ?? ''),
            'comingSoonTitle' => (string) ($maintenance['comingSoonTitle'] ?? $defaults['comingSoonTitle'] ?? ''),
            'comingSoonSubtitle' => (string) ($maintenance['comingSoonSubtitle'] ?? $defaults['comingSoonSubtitle'] ?? ''),
            'comingSoonBody' => (string) ($maintenance['comingSoonBody'] ?? ''),
            'maintenanceBadge' => (string) ($maintenance['maintenanceBadge'] ?? $defaults['maintenanceBadge'] ?? ''),
            'maintenanceTitle' => (string) ($maintenance['maintenanceTitle'] ?? $defaults['maintenanceTitle'] ?? ''),
            'maintenanceSubtitle' => (string) ($maintenance['maintenanceSubtitle'] ?? $defaults['maintenanceSubtitle'] ?? ''),
            'maintenanceBody' => (string) ($maintenance['maintenanceBody'] ?? ''),
            'maintenanceShowContactForm' => (bool) ($maintenance['maintenanceShowContactForm'] ?? true),
            'maintenanceContactSubject' => (string) ($maintenance['maintenanceContactSubject'] ?? $defaults['maintenanceContactSubject'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $marketing
     * @return array<string, mixed>
     */
    private function publicDemoSettings(array $marketing): array
    {
        if ($this->demoMode->isEnabled()) {
            return [
                'enabled' => true,
                'url' => $this->demoMode->publicDemoUrl(),
                'loginEmail' => DemoFixtures::ADMIN_EMAIL,
                'autoResetMinutes' => $this->demoMode->autoResetMinutes(),
                'showFooterLink' => false,
            ];
        }

        $defaults = SettingsSchema::defaults()['marketing'] ?? [];
        $demoUrl = trim((string) ($marketing['demoUrl'] ?? $defaults['demoUrl'] ?? ''));
        if ($demoUrl === '') {
            $demoUrl = $this->demoMode->publicDemoUrl();
        }

        return [
            'enabled' => false,
            'url' => $demoUrl,
            'showFooterLink' => (bool) ($marketing['demoFooterLinkEnabled'] ?? $defaults['demoFooterLinkEnabled'] ?? true),
            'autoResetMinutes' => null,
        ];
    }

    /**
     * @param array<string, mixed> $marketing
     * @return array{enabled: bool, links: list<array{platform: string, url: string, label: string}>}
     */
    private function publicSocialSettings(array $marketing): array
    {
        $defaults = SettingsSchema::defaults()['marketing'] ?? [];
        $enabled = (bool) ($marketing['socialLinksEnabled'] ?? $defaults['socialLinksEnabled'] ?? true);
        $raw = trim((string) ($marketing['socialLinksJson'] ?? ''));

        if ($raw === '') {
            $raw = SocialLinksNormalizer::encode(SocialLinksNormalizer::defaults());
        }

        return [
            'enabled' => $enabled,
            'links' => SocialLinksNormalizer::publicLinks($raw, $enabled),
        ];
    }

    /**
     * @param array<string, mixed> $gallery
     * @return array{enabled: bool, placement: string, publicRoute: string, layout: string, showFeatureTags: bool}
     */
    private function publicGallerySettings(array $gallery): array
    {
        $defaults = SettingsSchema::defaults()['gallery'] ?? [];

        return [
            'enabled' => (bool) ($gallery['enabled'] ?? $defaults['enabled'] ?? false),
            'placement' => (string) ($gallery['placement'] ?? $defaults['placement'] ?? 'route'),
            'publicRoute' => (string) ($gallery['publicRoute'] ?? $defaults['publicRoute'] ?? '/features'),
            'layout' => (string) ($gallery['layout'] ?? $defaults['layout'] ?? 'grid'),
            'showFeatureTags' => (bool) ($gallery['showFeatureTags'] ?? $defaults['showFeatureTags'] ?? true),
        ];
    }

    /**
     * @param array<string, mixed> $newsletter
     * @return array<string, mixed>
     */
    private function publicNewsletterSettings(array $newsletter): array
    {
        $defaults = SettingsSchema::defaults()['newsletter'] ?? [];

        return [
            'footerEnabled' => (bool) ($newsletter['footerEnabled'] ?? $defaults['footerEnabled'] ?? false),
            'footerHint' => (string) ($newsletter['footerHint'] ?? $defaults['footerHint'] ?? ''),
            'enabledPreferences' => \PaginiumCMS\Modules\Newsletter\Support\NewsletterPreferences::parseEnabledList(
                (string) ($newsletter['enabledPreferences'] ?? $defaults['enabledPreferences'] ?? '')
            ),
            'requireConsentCheckbox' => (bool) (
                $newsletter['requireConsentCheckbox'] ?? $defaults['requireConsentCheckbox'] ?? false
            ),
            'requireDoubleOptIn' => (bool) (
                $newsletter['requireDoubleOptIn'] ?? $defaults['requireDoubleOptIn'] ?? false
            ),
        ];
    }

    /**
     * @param array<string, mixed> $privacy
     * @return array<string, mixed>
     */
    private function publicPrivacySettings(array $privacy): array
    {
        $defaults = SettingsSchema::defaults()['privacy'] ?? [];

        return [
            'cookieBannerEnabled' => (bool) ($privacy['cookieBannerEnabled'] ?? $defaults['cookieBannerEnabled'] ?? false),
            'cookieBannerText' => (string) ($privacy['cookieBannerText'] ?? $defaults['cookieBannerText'] ?? ''),
            'cookiePolicyUrl' => (string) ($privacy['cookiePolicyUrl'] ?? $defaults['cookiePolicyUrl'] ?? ''),
            'cookieShowRejectButton' => (bool) (
                $privacy['cookieShowRejectButton'] ?? $defaults['cookieShowRejectButton'] ?? true
            ),
        ];
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

    private function isSuperAdmin(?User $user): bool
    {
        return $user instanceof User && in_array('SUPER_ADMIN', $user->getRoles(), true);
    }

    /**
     * @param array<string, array<string, mixed>> $values
     * @return array<string, array<string, mixed>>
     */
    private function enrichAccessControlValues(array $values): array
    {
        if (!isset($values['accessControl'])) {
            $values['accessControl'] = SettingsSchema::defaults()['accessControl'] ?? [];
        }

        $values['accessControl'] = $this->mergeLegacyPathAcl($values['accessControl']);

        return $values;
    }

    /**
     * @param array<string, mixed> $accessControl
     * @return array<string, mixed>
     */
    private function mergeLegacyPathAcl(array $accessControl): array
    {
        $defaults = SettingsSchema::defaults()['accessControl'] ?? [];
        $storedRules = (string) ($accessControl['pathAclRulesJson'] ?? '[]');
        $defaultRules = (string) ($defaults['pathAclRulesJson'] ?? '[]');

        if ($storedRules !== $defaultRules) {
            return $accessControl;
        }

        $fromAcl = $this->accessControlSync->pathAclSettingsFromRepository();
        if (($fromAcl['pathAclRulesJson'] ?? '[]') === '[]' && !($fromAcl['pathAclEnabled'] ?? false)) {
            return $accessControl;
        }

        return array_merge($accessControl, $fromAcl);
    }

    /**
     * @return array{
     *     productName: string,
     *     version: string,
     *     license: string,
     *     licenseUrl: string,
     *     repositoryUrl: string,
     *     documentationUrl: string,
     *     philosophyUrl: string,
     *     changelogUrl: string,
     *     phpVersion: string,
     *     stack: array{backend: string, frontend: string, storage: string},
     *     locales: list<array{code: string, label: string, builtin?: bool}>
     * }
     */
    private function buildCmsInfoMeta(): array
    {
        $repositoryUrl = 'https://github.com/techberode/paginiumcms-architecture';

        return [
            'productName' => 'PaginiumCMS',
            'version' => AppVersion::current(),
            'license' => 'MIT',
            'licenseUrl' => $repositoryUrl . '/blob/main/LICENSE',
            'repositoryUrl' => $repositoryUrl,
            'documentationUrl' => $repositoryUrl . '/blob/main/docs/README.md',
            'philosophyUrl' => $repositoryUrl . '/blob/main/docs/PHILOSOPHY.md',
            'changelogUrl' => $repositoryUrl . '/blob/main/CHANGELOG.md',
            'phpVersion' => PHP_VERSION,
            'stack' => [
                'backend' => 'PHP ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . ' · Slim 4 · flat-file',
                'frontend' => 'React · TypeScript · Vite 8',
                'storage' => 'JSON / Markdown on disk (no SQL)',
            ],
            'locales' => $this->localesRegistry->all(),
        ];
    }
}
