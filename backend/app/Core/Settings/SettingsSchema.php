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
 */public static function groups(): array
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
                    ['key' => 'allowRegistration', 'type' => 'bool', 'label' => 'Povoliť registráciu', 'default' => true, 'rules' => ['bool'], 'help' => 'Vypnutím zablokujete POST /api/auth/register.'],
                ],
            ],
            'content' => [
                'label' => 'Obsah',
                'fields' => [
                    ['key' => 'itemsPerPage', 'type' => 'int', 'label' => 'Položiek na stránku (admin)', 'default' => 20, 'rules' => ['required', 'int', 'min:1', 'max:100'], 'help' => 'Admin zoznamy stránok a článkov.'],
                    ['key' => 'blogItemsPerPage', 'type' => 'int', 'label' => 'Článkov na stránku (blog)', 'default' => 6, 'rules' => ['required', 'int', 'min:1', 'max:100'], 'help' => 'Verejný zoznam článkov – stránkovanie sa zobrazí, keď je viac článkov.'],
                    ['key' => 'storageFormat', 'type' => 'enum', 'label' => 'Formát úložiska obsahu', 'default' => 'md', 'options' => ['md', 'json'], 'rules' => ['required', 'in:md,json'], 'help' => 'md = YAML front matter + Markdown; json = čistý JSON súbor (Iterácia 19).'],
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
                    ['key' => 'ntfyAuthMode', 'type' => 'enum', 'label' => 'ntfy authentication', 'default' => 'none', 'options' => ['none', 'token', 'basic'], 'rules' => ['required', 'in:none,token,basic'], 'help' => 'Use token for ntfy.sh ACL topics or Basic for self-hosted instances.'],
                    ['key' => 'ntfyAccessToken', 'type' => 'password', 'label' => 'ntfy access token', 'default' => '', 'rules' => ['string', 'max:512']],
                    ['key' => 'ntfyUsername', 'type' => 'string', 'label' => 'ntfy username (Basic auth)', 'default' => '', 'rules' => ['string', 'max:120']],
                    ['key' => 'ntfyPassword', 'type' => 'password', 'label' => 'ntfy password (Basic auth)', 'default' => '', 'rules' => ['string', 'max:255']],
                    ['key' => 'discordEnabled', 'type' => 'bool', 'label' => 'Enable Discord webhook', 'default' => false, 'rules' => ['bool']],
                    ['key' => 'discordWebhookUrl', 'type' => 'url', 'label' => 'Discord webhook URL', 'default' => '', 'rules' => ['url', 'max:512']],
                    ['key' => 'telegramEnabled', 'type' => 'bool', 'label' => 'Enable Telegram bot', 'default' => false, 'rules' => ['bool']],
                    ['key' => 'telegramBotToken', 'type' => 'password', 'label' => 'Telegram bot token', 'default' => '', 'rules' => ['string', 'max:255']],
                    ['key' => 'telegramChatId', 'type' => 'string', 'label' => 'Telegram chat ID', 'default' => '', 'rules' => ['string', 'max:64']],
                    ['key' => 'webhookEnabled', 'type' => 'bool', 'label' => 'Enable generic webhook', 'default' => false, 'rules' => ['bool']],
                    ['key' => 'webhookUrl', 'type' => 'url', 'label' => 'Webhook URL', 'default' => '', 'rules' => ['url', 'max:512']],
                    ['key' => 'webhookSecret', 'type' => 'password', 'label' => 'Webhook secret (optional)', 'default' => '', 'rules' => ['string', 'max:255']],
                    ['key' => 'webhookAuthHeader', 'type' => 'string', 'label' => 'Webhook auth header name', 'default' => 'X-Webhook-Secret', 'rules' => ['string', 'max:120'], 'help' => 'HTTP header used to send webhookSecret when set.'],
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
                    ['key' => 'reportsEnabled', 'type' => 'bool', 'label' => 'Enable scheduled monitoring reports', 'default' => false, 'rules' => ['bool'], 'help' => 'Requires cron: php backend/bin/console monitoring:run-schedule'],
                    ['key' => 'reportInterval', 'type' => 'enum', 'label' => 'Report interval', 'default' => 'day', 'options' => ['hour', 'day', 'week'], 'rules' => ['required', 'in:hour,day,week']],
                    ['key' => 'reportTime', 'type' => 'string', 'label' => 'Send time (HH:MM)', 'default' => '08:00', 'rules' => ['required', 'string', 'max:5'], 'help' => 'Used for daily and weekly reports (site timezone).'],
                    ['key' => 'reportWeekday', 'type' => 'enum', 'label' => 'Weekly report day', 'default' => 'mon', 'options' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'], 'rules' => ['required', 'in:mon,tue,wed,thu,fri,sat,sun']],
                    ['key' => 'reportMinute', 'type' => 'int', 'label' => 'Hourly report minute (0–59)', 'default' => 0, 'rules' => ['int', 'min:0', 'max:59'], 'help' => 'For hourly interval – minute past each hour.'],
                    ['key' => 'reportConnector', 'type' => 'enum', 'label' => 'Report connector', 'default' => 'email', 'options' => ['email', 'ntfy', 'discord', 'telegram', 'webhook', 'all'], 'rules' => ['required', 'in:email,ntfy,discord,telegram,webhook,all']],
                    ['key' => 'reportIncludeAnalytics', 'type' => 'bool', 'label' => 'Report: analytics stats', 'default' => true, 'rules' => ['bool']],
                    ['key' => 'reportIncludeHealth', 'type' => 'bool', 'label' => 'Report: system health', 'default' => true, 'rules' => ['bool']],
                    ['key' => 'reportIncludeFlatFile', 'type' => 'bool', 'label' => 'Report: flat-file counts', 'default' => true, 'rules' => ['bool']],
                    ['key' => 'notifyLogErrors', 'type' => 'bool', 'label' => 'Alert on log ERROR/CRITICAL', 'default' => true, 'rules' => ['bool']],
                    ['key' => 'notifyLogWarnings', 'type' => 'bool', 'label' => 'Alert on log WARNING', 'default' => false, 'rules' => ['bool']],
                    ['key' => 'logIncidentConnector', 'type' => 'enum', 'label' => 'Log incident connector', 'default' => 'all', 'options' => ['email', 'ntfy', 'discord', 'telegram', 'webhook', 'all'], 'rules' => ['required', 'in:email,ntfy,discord,telegram,webhook,all']],
                ],
            ],
            'scheduler' => [
                'label' => 'Job scheduler',
                'fields' => [
                    ['key' => 'enabled', 'type' => 'bool', 'label' => 'Enable job scheduler', 'default' => true, 'rules' => ['bool'], 'help' => 'Master switch for scheduler:run CLI. Individual jobs can still be toggled in Plánovač.'],
                    ['key' => 'retainRuns', 'type' => 'int', 'label' => 'Retain run history entries', 'default' => 200, 'rules' => ['int', 'min:50', 'max:500']],
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
            'comments' => [
                'label' => 'Komentáre',
                'fields' => [
                    ['key' => 'enabled', 'type' => 'bool', 'label' => 'Povoliť komentáre globálne', 'default' => true, 'rules' => ['bool'], 'help' => 'Vypnutím sa skryje formulár na celom webe (okrem článkov s vlastným prepínačom).'],
                    ['key' => 'requireApproval', 'type' => 'bool', 'label' => 'Globálne vyžadovať schválenie', 'default' => true, 'rules' => ['bool'], 'help' => 'Nové komentáre čakajú na schválenie v administrácii. Dá sa prepísať pri jednotlivom článku.'],
                    ['key' => 'allowGuestComments', 'type' => 'bool', 'label' => 'Povoliť komentáre od hostí', 'default' => true, 'rules' => ['bool'], 'help' => 'Neprihlásení návštevníci môžu pridávať komentáre. Dá sa prepísať pri jednotlivom článku.'],
                    ['key' => 'maxLength', 'type' => 'int', 'label' => 'Max. dĺžka komentára', 'default' => 2000, 'rules' => ['required', 'int', 'min:50', 'max:5000']],
                ],
            ],
            'workflows' => [
                'label' => 'Workflow OTP',
                'fields' => [
                    ['key' => 'registrationOtpEnabled', 'type' => 'bool', 'label' => 'OTP pri registrácii', 'default' => false, 'rules' => ['bool'], 'help' => 'Nový účet vznikne až po overení e-mailového kódu (Iterácia 41).'],
                    ['key' => 'commentApprovalOtpEnabled', 'type' => 'bool', 'label' => 'OTP pri schválení komentára', 'default' => false, 'rules' => ['bool'], 'help' => 'Editor musí potvrdiť schválenie komentára kódom z mailu.'],
                    ['key' => 'publishApprovalOtpEnabled', 'type' => 'bool', 'label' => 'OTP pri publikácii', 'default' => false, 'rules' => ['bool'], 'help' => 'Editor musí potvrdiť publikáciu príspevku kódom z mailu.'],
                    ['key' => 'otpTtlMinutes', 'type' => 'int', 'label' => 'Platnosť OTP kódu (min)', 'default' => 15, 'rules' => ['required', 'int', 'min:5', 'max:120']],
                    ['key' => 'otpMaxAttempts', 'type' => 'int', 'label' => 'Max. pokusov OTP', 'default' => 5, 'rules' => ['required', 'int', 'min:3', 'max:10']],
                ],
            ],
            'ui' => [
                'label' => 'Admin UI',
                'fields' => [
                    ['key' => 'showListCounts', 'type' => 'bool', 'label' => 'Zobraziť počty v sidebari', 'default' => true, 'rules' => ['bool'], 'help' => 'Badge s počtom položiek pri moduloch v administrácii (Iterácia 42).'],
                    ['key' => 'adminListPageSize', 'type' => 'int', 'label' => 'Položiek na stránku (admin)', 'default' => 20, 'rules' => ['required', 'int', 'min:5', 'max:100'], 'help' => 'Predvolený počet riadkov v admin zoznamoch (Media, Kôš, komentáre…).'],
                    ['key' => 'openLinksInNewTab', 'type' => 'bool', 'label' => 'Otvárať náhľady a externé odkazy v novej karte', 'default' => false, 'rules' => ['bool'], 'help' => 'Platí pre náhľad obsahu, prechod na verejný web z adminu, media download a externé odkazy vo footeri. Vypnuté = rovnaká karta / SPA navigácia.'],
                ],
            ],
            'security' => [
                'label' => 'Bezpečnosť',
                'fields' => [
                    ['key' => 'maxLoginAttempts', 'type' => 'int', 'label' => 'Max. neúspešných prihlásení', 'default' => 5, 'rules' => ['required', 'int', 'min:3', 'max:20'], 'help' => 'Po prekročení sa účet/IP dočasne zablokuje.'],
                    ['key' => 'lockoutMinutes', 'type' => 'int', 'label' => 'Dĺžka blokácie (min)', 'default' => 15, 'rules' => ['required', 'int', 'min:1', 'max:1440']],
                    ['key' => 'requireTwoFactorStaff', 'type' => 'bool', 'label' => 'Vynútiť 2FA pre editorov a adminov', 'default' => true, 'rules' => ['bool'], 'help' => 'Pri zapnutí nie je možné vypnúť 2FA pre roly EDITOR, ADMIN a SUPER_ADMIN.'],
                ],
            ],
            'firewall' => [
                'label' => 'Firewall (WAF)',
                'fields' => [
                    ['key' => 'enabled', 'type' => 'bool', 'label' => 'Zapnúť firewall', 'default' => true, 'rules' => ['bool'], 'help' => 'Interný WAF skenuje URI, query a User-Agent pred spracovaním požiadavky.'],
                    ['key' => 'jailMinutes', 'type' => 'int', 'label' => 'Dĺžka jail (min)', 'default' => 15, 'rules' => ['required', 'int', 'min:1', 'max:1440'], 'help' => 'Dočasná blokácia IP po prekročení prahu incidentov.'],
                    ['key' => 'maxRetries', 'type' => 'int', 'label' => 'Incidentov pred jail', 'default' => 3, 'rules' => ['required', 'int', 'min:1', 'max:20'], 'help' => 'Počet porušení v okne pred dočasným banom.'],
                    ['key' => 'permanentThreshold', 'type' => 'int', 'label' => 'Prah trvalého banu', 'default' => 3, 'rules' => ['required', 'int', 'min:1', 'max:20'], 'help' => 'Počet jail cyklov pred trvalou blokáciou IP.'],
                    ['key' => 'jailMode', 'type' => 'enum', 'label' => 'Jail odpoveď', 'default' => 'forbidden', 'options' => ['forbidden', 'empty', 'tarpit'], 'rules' => ['required', 'in:forbidden,empty,tarpit'], 'help' => 'Režim HTTP odpovede pre zablokované IP. Tarpit spomaľuje botov (max 2 s).'],
                    ['key' => 'tarpitSeconds', 'type' => 'int', 'label' => 'Tarpit oneskorenie (s)', 'default' => 0, 'rules' => ['int', 'min:0', 'max:2'], 'help' => 'Platí len pri jailMode=tarpit. Neodporúčame >2 s (FPM worker).'],
                    ['key' => 'logRetention', 'type' => 'int', 'label' => 'Max. incidentov v logu', 'default' => 500, 'rules' => ['required', 'int', 'min:50', 'max:5000']],
                ],
            ],
            'logging' => [
                'label' => 'Logy',
                'fields' => [
                    ['key' => 'enabled', 'type' => 'bool', 'label' => 'Zapnúť logovanie', 'default' => true, 'rules' => ['bool'], 'help' => 'Master prepínač structured logov (app, audit, event, user).'],
                    ['key' => 'requestLogging', 'type' => 'bool', 'label' => 'Logovať HTTP requesty', 'default' => true, 'rules' => ['bool'], 'help' => 'Každý API endpoint → záznam s timestamp, IP, status, duration.'],
                    ['key' => 'minSeverity', 'type' => 'enum', 'label' => 'Min. úroveň zápisu', 'default' => 'debug', 'options' => ['debug', 'info', 'warning', 'error', 'critical'], 'rules' => ['required', 'in:debug,info,warning,error,critical'], 'help' => 'Nižšie úrovne sa neukladajú (HTTP access log).'],
                    ['key' => 'retentionDays', 'type' => 'int', 'label' => 'Retencia logov (dni)', 'default' => 30, 'rules' => ['required', 'int', 'min:1', 'max:365'], 'help' => 'Staršie denné súbory sa vymažú (purge v admin Logy).'],
                    ['key' => 'slowRequestMs', 'type' => 'int', 'label' => 'Pomalý request (ms)', 'default' => 2000, 'rules' => ['required', 'int', 'min:100', 'max:60000'], 'help' => 'Requesty nad tento limit sa logujú ako WARNING.'],
                    ['key' => 'logAuthEndpoints', 'type' => 'bool', 'label' => 'Logovať auth endpointy', 'default' => false, 'rules' => ['bool'], 'help' => 'Login/register cesty — bez tela, len metadata (IP, status).'],
                ],
            ],
            'feeds' => [
                'label' => 'RSS & Sitemap',
                'fields' => [
                    ['key' => 'enabled', 'type' => 'bool', 'label' => 'Povoliť feedy', 'default' => true, 'rules' => ['bool']],
                    ['key' => 'title', 'type' => 'string', 'label' => 'Názov RSS kanála', 'default' => '', 'rules' => ['string', 'max:120'], 'help' => 'Prázdne = názov stránky z všeobecných nastavení.'],
                    ['key' => 'description', 'type' => 'text', 'label' => 'Popis RSS kanála', 'default' => '', 'rules' => ['string', 'max:500']],
                    ['key' => 'itemsLimit', 'type' => 'int', 'label' => 'Počet položiek v RSS', 'default' => 20, 'rules' => ['required', 'int', 'min:1', 'max:100']],
                    ['key' => 'includePages', 'type' => 'bool', 'label' => 'Sitemap: podstránky', 'default' => true, 'rules' => ['bool']],
                    ['key' => 'includeArticles', 'type' => 'bool', 'label' => 'RSS/Sitemap: články', 'default' => true, 'rules' => ['bool']],
                ],
            ],
            'seo' => [
                'label' => 'SEO',
                'fields' => [
                    ['key' => 'titleTemplate', 'type' => 'string', 'label' => 'Šablóna titulku', 'default' => '%title% | %siteName%', 'rules' => ['required', 'string', 'max:120'], 'help' => 'Placeholders: %title%, %siteName%'],
                    ['key' => 'defaultDescription', 'type' => 'text', 'label' => 'Predvolený meta popis', 'default' => '', 'rules' => ['string', 'max:300']],
                    ['key' => 'defaultImage', 'type' => 'url', 'label' => 'Predvolený OG obrázok (URL)', 'default' => '', 'rules' => ['url', 'max:512']],
                    ['key' => 'robotsDefault', 'type' => 'string', 'label' => 'Robots (predvolene)', 'default' => 'index,follow', 'rules' => ['required', 'string', 'max:64']],
                    ['key' => 'twitterCard', 'type' => 'enum', 'label' => 'Twitter card typ', 'default' => 'summary_large_image', 'options' => ['summary', 'summary_large_image'], 'rules' => ['required', 'in:summary,summary_large_image']],
                ],
            ],
            'media' => [
                'label' => 'Media / DAM',
                'fields' => [
                    ['key' => 'allowedMimeTypes', 'type' => 'text', 'label' => 'Povolené MIME typy', 'default' => 'image/jpeg,image/png,image/gif,image/webp,image/svg+xml,application/pdf', 'rules' => ['required', 'string', 'max:2000'], 'help' => 'Oddeľte čiarkou. Ovplyvňuje upload v Media Library.'],
                    ['key' => 'maxUploadSizeKb', 'type' => 'int', 'label' => 'Max. veľkosť uploadu (KB)', 'default' => 5120, 'rules' => ['required', 'int', 'min:64', 'max:51200'], 'help' => '5120 KB = 5 MB.'],
                    ['key' => 'stockImagesEnabled', 'type' => 'bool', 'label' => 'Povoliť stock knižnicu', 'default' => true, 'rules' => ['bool'], 'help' => 'Tlačidlo „Generovať z knižnice“ v Media Library.'],
                    ['key' => 'stockImageTopic', 'type' => 'enum', 'label' => 'Téma stock obrázkov', 'default' => 'tech', 'options' => ['tech', 'business', 'food', 'travel', 'health', 'nature', 'general'], 'rules' => ['required', 'in:tech,business,food,travel,health,nature,general'], 'help' => 'Obrázky sa vyberajú podľa zamerania webu (IT, varenie, cestovanie…).'],
                ],
            ],
            'sso' => [
                'label' => 'SSO / OAuth',
                'fields' => [
                    ['key' => 'enabled', 'type' => 'bool', 'label' => 'Povoliť SSO prihlásenie', 'default' => false, 'rules' => ['bool']],
                    ['key' => 'defaultRole', 'type' => 'enum', 'label' => 'Predvolená rola (nový účet)', 'default' => 'EDITOR', 'options' => ['USER', 'EDITOR', 'ADMIN'], 'rules' => ['required', 'in:USER,EDITOR,ADMIN']],
                    ['key' => 'githubEnabled', 'type' => 'bool', 'label' => 'GitHub OAuth', 'default' => false, 'rules' => ['bool']],
                    ['key' => 'githubClientId', 'type' => 'string', 'label' => 'GitHub Client ID', 'default' => '', 'rules' => ['string', 'max:255']],
                    ['key' => 'githubClientSecret', 'type' => 'password', 'label' => 'GitHub Client Secret', 'default' => '', 'rules' => ['string', 'max:255']],
                    ['key' => 'genericEnabled', 'type' => 'bool', 'label' => 'Generic OAuth2', 'default' => false, 'rules' => ['bool']],
                    ['key' => 'genericName', 'type' => 'string', 'label' => 'Generic provider name', 'default' => 'OAuth', 'rules' => ['string', 'max:64']],
                    ['key' => 'genericClientId', 'type' => 'string', 'label' => 'Generic Client ID', 'default' => '', 'rules' => ['string', 'max:255']],
                    ['key' => 'genericClientSecret', 'type' => 'password', 'label' => 'Generic Client Secret', 'default' => '', 'rules' => ['string', 'max:255']],
                    ['key' => 'genericAuthorizeUrl', 'type' => 'url', 'label' => 'Authorize URL', 'default' => '', 'rules' => ['url', 'max:512']],
                    ['key' => 'genericTokenUrl', 'type' => 'url', 'label' => 'Token URL', 'default' => '', 'rules' => ['url', 'max:512']],
                    ['key' => 'genericUserInfoUrl', 'type' => 'url', 'label' => 'UserInfo URL', 'default' => '', 'rules' => ['url', 'max:512']],
                    ['key' => 'genericScope', 'type' => 'string', 'label' => 'OAuth scope', 'default' => 'openid email profile', 'rules' => ['string', 'max:255']],
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
 */public static function rulesFor(string $group): array
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
