<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Settings;

/**
 * === Definícia: SettingsSchema ===
 * Jediný zdroj pravdy pre štruktúru nastavení CMS (Iterácia 4).
 *
 * Schéma je riadená dátami: každá skupina obsahuje polia s typom, popisom,
 * predvolenou hodnotou a validačnými pravidlami. Frontend z nej vykresľuje
 * generický formulár, backend z nej odvodzuje validáciu aj predvolené hodnoty.
 *
 * Rozšírenie v ďalších iteráciách (SMTP, notifikácie, SEO, feedy) = pridanie
 * novej skupiny sem. Žiadna ďalšia zmena v engine/controlleri nie je potrebná.
 *
 * @phpstan-type SettingField array{
 *     key: string,
 *     type: string,
 *     label: string,
 *     default: mixed,
 *     rules: list<string>,
 *     help?: string,
 *     options?: list<string>
 * }
 * @phpstan-type SettingGroup array{label: string, fields: list<SettingField>}
 */
final class SettingsSchema
{
    /**
     * Celá schéma nastavení.
     *
     * @return array<string, SettingGroup>
     */
    public static function groups(): array
    {
        return [
            'general' => [
                'label' => 'Všeobecné',
                'fields' => [
                    ['key' => 'siteName', 'type' => 'string', 'label' => 'Názov stránky', 'default' => 'PaginiumCMS', 'rules' => ['required', 'string', 'min:2', 'max:120']],
                    ['key' => 'siteDescription', 'type' => 'text', 'label' => 'Popis stránky', 'default' => '', 'rules' => ['string', 'max:300']],
                    ['key' => 'siteUrl', 'type' => 'url', 'label' => 'URL stránky', 'default' => '', 'rules' => ['url', 'max:255'], 'help' => 'Napr. https://example.com'],
                    ['key' => 'adminEmail', 'type' => 'email', 'label' => 'Administrátorský e-mail', 'default' => '', 'rules' => ['email', 'max:255']],
                    ['key' => 'language', 'type' => 'enum', 'label' => 'Jazyk administrácie', 'default' => 'sk', 'options' => ['sk', 'en'], 'rules' => ['required', 'in:sk,en']],
                    ['key' => 'timezone', 'type' => 'string', 'label' => 'Časové pásmo', 'default' => 'Europe/Bratislava', 'rules' => ['required', 'string', 'max:64']],
                    ['key' => 'maintenanceMode', 'type' => 'bool', 'label' => 'Režim údržby', 'default' => false, 'rules' => ['bool'], 'help' => 'Zablokuje verejný web okrem administrácie.'],
                ],
            ],
            'content' => [
                'label' => 'Obsah',
                'fields' => [
                    ['key' => 'itemsPerPage', 'type' => 'int', 'label' => 'Položiek na stránku', 'default' => 20, 'rules' => ['required', 'int', 'min:1', 'max:100']],
                    ['key' => 'defaultStatus', 'type' => 'enum', 'label' => 'Predvolený stav obsahu', 'default' => 'draft', 'options' => ['draft', 'published'], 'rules' => ['required', 'in:draft,published']],
                    ['key' => 'autoSaveInterval', 'type' => 'int', 'label' => 'Interval auto-save (s)', 'default' => 60, 'rules' => ['required', 'int', 'min:10', 'max:600'], 'help' => 'Ako často sa ukladá koncept (Iterácia 2).'],
                    ['key' => 'lockTtl', 'type' => 'int', 'label' => 'Platnosť zámku obsahu (s)', 'default' => 300, 'rules' => ['required', 'int', 'min:60', 'max:3600'], 'help' => 'Auto-release zámku po nečinnosti (Iterácia 1).'],
                ],
            ],
            'editor' => [
                'label' => 'Editor',
                'fields' => [
                    ['key' => 'defaultEditor', 'type' => 'enum', 'label' => 'Predvolený editor', 'default' => 'markdown', 'options' => ['markdown', 'wysiwyg'], 'rules' => ['required', 'in:markdown,wysiwyg']],
                    ['key' => 'spellcheck', 'type' => 'bool', 'label' => 'Kontrola pravopisu', 'default' => true, 'rules' => ['bool']],
                    ['key' => 'tabSize', 'type' => 'int', 'label' => 'Veľkosť tabulátora', 'default' => 2, 'rules' => ['required', 'int', 'min:2', 'max:8']],
                ],
            ],
            'smtp' => [
                'label' => 'Email / SMTP',
                'fields' => [
                    ['key' => 'enabled', 'type' => 'bool', 'label' => 'Enable SMTP', 'default' => false, 'rules' => ['bool']],
                    ['key' => 'host', 'type' => 'string', 'label' => 'SMTP host', 'default' => '', 'rules' => ['string', 'max:255']],
                    ['key' => 'port', 'type' => 'int', 'label' => 'SMTP port', 'default' => 587, 'rules' => ['int', 'min:1', 'max:65535']],
                    ['key' => 'encryption', 'type' => 'enum', 'label' => 'Encryption', 'default' => 'tls', 'options' => ['none', 'tls', 'ssl'], 'rules' => ['in:none,tls,ssl']],
                    ['key' => 'username', 'type' => 'string', 'label' => 'SMTP username', 'default' => '', 'rules' => ['string', 'max:255']],
                    ['key' => 'password', 'type' => 'password', 'label' => 'SMTP password', 'default' => '', 'rules' => ['string', 'max:255'], 'help' => 'Stored in settings.json; never exposed via public API.'],
                    ['key' => 'fromEmail', 'type' => 'email', 'label' => 'From email', 'default' => '', 'rules' => ['email', 'max:255']],
                    ['key' => 'fromName', 'type' => 'string', 'label' => 'From name', 'default' => 'PaginiumCMS', 'rules' => ['string', 'max:120']],
                ],
            ],
            'notifications' => [
                'label' => 'Toast notifications',
                'fields' => [
                    ['key' => 'toastEnabled', 'type' => 'bool', 'label' => 'Enable toast notifications', 'default' => true, 'rules' => ['bool']],
                    ['key' => 'toastPosition', 'type' => 'enum', 'label' => 'Toast position', 'default' => 'top-right', 'options' => ['top-right', 'top-left', 'bottom-right', 'bottom-left'], 'rules' => ['required', 'in:top-right,top-left,bottom-right,bottom-left']],
                    ['key' => 'toastDuration', 'type' => 'int', 'label' => 'Default duration (ms)', 'default' => 3000, 'rules' => ['required', 'int', 'min:1000', 'max:30000']],
                    ['key' => 'toastDebugMode', 'type' => 'bool', 'label' => 'Debug mode (longer toasts, console log)', 'default' => false, 'rules' => ['bool'], 'help' => 'Useful when developing modules, code editor, and content workflows.'],
                ],
            ],
            'connectors' => [
                'label' => 'Notification connectors',
                'fields' => [
                    ['key' => 'emailEnabled', 'type' => 'bool', 'label' => 'Email channel (SMTP)', 'default' => false, 'rules' => ['bool']],
                    ['key' => 'ntfyEnabled', 'type' => 'bool', 'label' => 'Enable ntfy', 'default' => false, 'rules' => ['bool']],
                    ['key' => 'ntfyServer', 'type' => 'url', 'label' => 'ntfy server URL', 'default' => 'https://ntfy.sh', 'rules' => ['url', 'max:255']],
                    ['key' => 'ntfyTopic', 'type' => 'string', 'label' => 'ntfy topic', 'default' => '', 'rules' => ['string', 'max:120']],
                    ['key' => 'discordEnabled', 'type' => 'bool', 'label' => 'Enable Discord webhook', 'default' => false, 'rules' => ['bool']],
                    ['key' => 'discordWebhookUrl', 'type' => 'url', 'label' => 'Discord webhook URL', 'default' => '', 'rules' => ['url', 'max:512']],
                    ['key' => 'telegramEnabled', 'type' => 'bool', 'label' => 'Enable Telegram bot', 'default' => false, 'rules' => ['bool']],
                    ['key' => 'telegramBotToken', 'type' => 'password', 'label' => 'Telegram bot token', 'default' => '', 'rules' => ['string', 'max:255']],
                    ['key' => 'telegramChatId', 'type' => 'string', 'label' => 'Telegram chat ID', 'default' => '', 'rules' => ['string', 'max:64']],
                    ['key' => 'webhookEnabled', 'type' => 'bool', 'label' => 'Enable generic webhook', 'default' => false, 'rules' => ['bool']],
                    ['key' => 'webhookUrl', 'type' => 'url', 'label' => 'Webhook URL', 'default' => '', 'rules' => ['url', 'max:512']],
                    ['key' => 'webhookSecret', 'type' => 'password', 'label' => 'Webhook secret (optional)', 'default' => '', 'rules' => ['string', 'max:255']],
                ],
            ],
            'monitoring' => [
                'label' => 'Monitoring & incidents',
                'fields' => [
                    ['key' => 'alertsEnabled', 'type' => 'bool', 'label' => 'Enable incident alerts', 'default' => false, 'rules' => ['bool']],
                    ['key' => 'alertEmail', 'type' => 'email', 'label' => 'Fallback alert email', 'default' => '', 'rules' => ['email', 'max:255'], 'help' => 'Used when connectors are off; defaults to admin email.'],
                    ['key' => 'notifyFailedLogin', 'type' => 'bool', 'label' => 'Alert on failed login', 'default' => true, 'rules' => ['bool']],
                    ['key' => 'notifySecurityIncident', 'type' => 'bool', 'label' => 'Alert on security audit events', 'default' => true, 'rules' => ['bool']],
                    ['key' => 'notifyTrafficSpike', 'type' => 'bool', 'label' => 'Alert on traffic spike', 'default' => false, 'rules' => ['bool']],
                    ['key' => 'trafficSpikeThreshold', 'type' => 'int', 'label' => 'Traffic spike threshold (visits/hour)', 'default' => 500, 'rules' => ['int', 'min:10', 'max:100000']],
                    ['key' => 'minSeverity', 'type' => 'enum', 'label' => 'Minimum audit severity', 'default' => 'warning', 'options' => ['info', 'warning', 'error', 'critical'], 'rules' => ['required', 'in:info,warning,error,critical']],
                ],
            ],
            'codePolicy' => [
                'label' => 'Code policy',
                'fields' => [
                    ['key' => 'enabled', 'type' => 'bool', 'label' => 'Enable code policy checks', 'default' => true, 'rules' => ['bool']],
                    ['key' => 'strictMode', 'type' => 'bool', 'label' => 'Strict extension namespace rules', 'default' => false, 'rules' => ['bool']],
                    ['key' => 'maxFileSizeKb', 'type' => 'int', 'label' => 'Max file size (KB)', 'default' => 512, 'rules' => ['required', 'int', 'min:16', 'max:4096']],
                    ['key' => 'forbiddenPhpFunctions', 'type' => 'text', 'label' => 'Forbidden PHP functions', 'default' => 'eval,exec,shell_exec,system,passthru,proc_open,popen,assert,create_function', 'rules' => ['string', 'max:2000'], 'help' => 'Comma-separated list scanned before save.'],
                ],
            ],
        ];
    }

    /**
     * Predvolené hodnoty po skupinách.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function defaults(): array
    {
        $defaults = [];
        foreach (self::groups() as $group => $definition) {
            foreach ($definition['fields'] as $field) {
                $defaults[$group][$field['key']] = $field['default'];
            }
        }

        return $defaults;
    }

    /**
     * Validačné pravidlá pre jednu skupinu (pole => zoznam pravidiel).
     *
     * @return array<string, list<string>>
     */
    public static function rulesFor(string $group): array
    {
        $rules = [];
        foreach (self::groups()[$group]['fields'] ?? [] as $field) {
            $rules[$field['key']] = $field['rules'];
        }

        return $rules;
    }

    public static function hasGroup(string $group): bool
    {
        return isset(self::groups()[$group]);
    }
}
