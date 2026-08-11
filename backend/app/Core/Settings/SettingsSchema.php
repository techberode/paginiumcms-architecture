<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Settings;

use PaginiumCMS\Modules\Security\Models\User;

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
 * @phpstan-type SettingGroup array{label: string, fields: list<SettingField>, superAdminOnly?: bool, informational?: bool}
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
                    ['key' => 'timezone', 'type' => 'timezone', 'label' => 'Časové pásmo', 'default' => 'Europe/Bratislava', 'rules' => ['required', 'string', 'timezone'], 'help' => 'Platí pre logy, audit a naplánované reporty.'],
                    ['key' => 'timezoneDst', 'type' => 'bool', 'label' => 'Letný čas (DST)', 'default' => true, 'rules' => ['bool'], 'help' => 'Zapnuté = automatická korekcia letného času podľa zvoleného pásma. Vypnuté = stály zimný čas bez posunu.'],
                    ['key' => 'allowRegistration', 'type' => 'bool', 'label' => 'Povoliť registráciu', 'default' => true, 'rules' => ['bool'], 'help' => 'Vypnutím zablokujete POST /api/auth/register. Počas režimu údržby je registrácia vždy vypnutá.'],
                ],
            ],
            'branding' => [
                'label' => 'Logo a favicon',
                'fields' => [
                    ['key' => 'logoUrl', 'type' => 'url', 'label' => 'Logo stránky (URL)', 'default' => '', 'rules' => ['string', 'max:512'], 'help' => 'Zobrazí sa vo verejnom menu, administrácii a maintenance stránkach. Odporúčané PNG/SVG do 512 px šírky.'],
                    ['key' => 'faviconUrl', 'type' => 'url', 'label' => 'Favicon (URL)', 'default' => '', 'rules' => ['string', 'max:512'], 'help' => 'Ikona v karte prehliadača. Odporúčané ICO, PNG alebo SVG (min. 32×32 px).'],
                ],
            ],
            'appearance' => [
                'label' => 'Vzhľad',
                'fields' => [
                    ['key' => 'colorScheme', 'type' => 'enum', 'label' => 'Farebná schéma', 'default' => 'indigo-classic', 'options' => ['indigo-classic', 'ocean-slate', 'forest-sage', 'sunset-rose', 'mono-zinc'], 'rules' => ['required', 'in:indigo-classic,ocean-slate,forest-sage,sunset-rose,mono-zinc'], 'help' => 'Predvolená paleta verejného webu (It.58b). Tokeny sa načítavajú z frontend katalógu.'],
                    ['key' => 'mode', 'type' => 'enum', 'label' => 'Režim zobrazenia', 'default' => 'system', 'options' => ['light', 'dark', 'system'], 'rules' => ['required', 'in:light,dark,system'], 'help' => 'Predvolený svetlý / tmavý režim alebo podľa systému.'],
                    ['key' => 'allowUserToggle', 'type' => 'bool', 'label' => 'Povoliť prepínač témy návštevníkom', 'default' => true, 'rules' => ['bool'], 'help' => 'Zobrazí prepínač svetlý/tmavý vo verejnom menu (localStorage).'],
                    ['key' => 'previewTemplate', 'type' => 'enum', 'label' => 'Náhľadová šablóna', 'default' => 'hero-content', 'options' => ['hero-content', 'single', 'two-column', 'landing', 'blog-article'], 'rules' => ['required', 'in:hero-content,single,two-column,landing,blog-article'], 'help' => 'Wireframe pre náhľad schémy v administrácii (It.58b/58c).'],
                ],
            ],
            'layout' => [
                'label' => 'Rozloženie stránky',
                'fields' => [
                    ['key' => 'builderMode', 'type' => 'enum', 'label' => 'Predvolený layout builder', 'default' => 'templates', 'options' => ['templates', 'shortcodes', 'outline', 'developer'], 'rules' => ['required', 'in:templates,shortcodes,outline,developer'], 'help' => 'Ktorý editor layoutu sa použije v admin chrome (It.58c). Shortcodes/outline/developer sa aktivujú v ďalších slice.'],
                    ['key' => 'defaultTemplate', 'type' => 'enum', 'label' => 'Predvolená layout šablóna', 'default' => 'hero-content', 'options' => ['single', 'hero-content', 'two-column', 'landing', 'blog-article'], 'rules' => ['required', 'in:single,hero-content,two-column,landing,blog-article'], 'help' => 'Štruktúra pre nové stránky a LayoutPreviewFrame (nie chrome template home/contact).'],
                    ['key' => 'developerRequiresAdmin', 'type' => 'bool', 'label' => 'Developer režim len pre ADMIN+', 'default' => true, 'rules' => ['bool'], 'help' => 'Ak je zapnuté, builderMode=developer môžu vybrať len ADMIN / SUPER_ADMIN.'],
                ],
            ],
            'content' => [
                'label' => 'Obsah',
                'fields' => [
                    ['key' => 'itemsPerPage', 'type' => 'int', 'label' => 'Položiek na stránku (admin)', 'default' => 20, 'rules' => ['required', 'int', 'min:1', 'max:100'], 'help' => 'Admin zoznamy stránok a článkov.'],
                    ['key' => 'blogItemsPerPage', 'type' => 'int', 'label' => 'Článkov na stránku (blog)', 'default' => 6, 'rules' => ['required', 'int', 'min:1', 'max:100'], 'help' => 'Verejný zoznam článkov – stránkovanie sa zobrazí, keď je viac článkov.'],
                    ['key' => 'showReadingTime', 'type' => 'bool', 'label' => 'Zobraziť odhadovaný čas čítania', 'default' => true, 'rules' => ['bool'], 'help' => 'Na blog kartách a detaile článku (počítané z dĺžky textu).'],
                    ['key' => 'storageFormat', 'type' => 'enum', 'label' => 'Formát úložiska obsahu', 'default' => 'md', 'options' => ['md', 'json'], 'rules' => ['required', 'in:md,json'], 'help' => 'md = YAML front matter + Markdown; json = čistý JSON súbor (Iterácia 19).'],
                    ['key' => 'defaultStatus', 'type' => 'enum', 'label' => 'Predvolený stav obsahu', 'default' => 'draft', 'options' => ['draft', 'published'], 'rules' => ['required', 'in:draft,published'], 'help' => 'Nové stránky/články vzniknú ako koncept alebo rovno publikované.'],
                    ['key' => 'autoSaveInterval', 'type' => 'int', 'label' => 'Interval auto-save (s)', 'default' => 60, 'rules' => ['required', 'int', 'min:10', 'max:600'], 'help' => 'Ako často sa ukladá koncept (Iterácia 2).'],
                    ['key' => 'lockTtl', 'type' => 'int', 'label' => 'Platnosť zámku obsahu (s)', 'default' => 300, 'rules' => ['required', 'int', 'min:60', 'max:3600'], 'help' => 'Auto-release zámku po nečinnosti (Iterácia 1).'],
                    ['key' => 'autoTagEnabled', 'type' => 'bool', 'label' => 'Navrhovanie tagov v editore', 'default' => true, 'rules' => ['bool'], 'help' => 'Povolí tlačidlo „Navrhnúť tagy“ v editore článkov (It.57).'],
                    ['key' => 'autoTagMax', 'type' => 'int', 'label' => 'Max. počet navrhovaných tagov', 'default' => 8, 'rules' => ['required', 'int', 'min:3', 'max:20'], 'help' => 'Koľko tagov vráti generátor naraz.'],
                    ['key' => 'autoDescriptionEnabled', 'type' => 'bool', 'label' => 'Generovanie meta popisu', 'default' => true, 'rules' => ['bool'], 'help' => 'Povolí tlačidlo „Generovať popis“ v editore (It.57).'],
                    ['key' => 'autoDescriptionMaxLength', 'type' => 'int', 'label' => 'Max. dĺžka meta popisu (znaky)', 'default' => 155, 'rules' => ['required', 'int', 'min:80', 'max:320'], 'help' => 'Odporúčané 150–160 znakov pre SEO.'],
                    ['key' => 'localeFallbackEnabled', 'type' => 'bool', 'label' => 'Povoliť locale fallback', 'default' => true, 'rules' => ['bool'], 'help' => 'Iteration 73: keď požadovaný jazyk chýba, vráti sa defaultLocale resource alebo site.'],
                    ['key' => 'localeNegotiationEnabled', 'type' => 'bool', 'label' => 'Accept-Language pre verejný obsah', 'default' => true, 'rules' => ['bool'], 'help' => 'Iteration 73: verejné GET stránok/článkov môže použiť Accept-Language ak chýba ?locale=.'],
                ],
            ],
            'editor' => [
                'label' => 'Editor',
                'fields' => [
                    ['key' => 'defaultEditor', 'type' => 'enum', 'label' => 'Predvolený editor', 'default' => 'markdown', 'options' => ['markdown', 'wysiwyg'], 'rules' => ['required', 'in:markdown,wysiwyg'], 'help' => 'Ktorý editor sa otvorí pri novom obsahu (Markdown alebo WYSIWYG).'],
                    ['key' => 'defaultProfilePage', 'type' => 'enum', 'label' => 'Predvolený profil (stránky)', 'default' => 'company', 'options' => ['company', 'blog', 'minimal', 'developer'], 'rules' => ['required', 'in:company,blog,minimal,developer'], 'help' => 'Modulárny toolbar pre stránky (Iterácia 54).'],
                    ['key' => 'defaultProfileArticle', 'type' => 'enum', 'label' => 'Predvolený profil (články)', 'default' => 'blog', 'options' => ['company', 'blog', 'minimal', 'developer'], 'rules' => ['required', 'in:company,blog,minimal,developer'], 'help' => 'Modulárny toolbar pre články (Iterácia 54).'],
                    ['key' => 'spellcheck', 'type' => 'bool', 'label' => 'Kontrola pravopisu', 'default' => true, 'rules' => ['bool'], 'help' => 'Zapnuté = prehliadač podčiarkne pravopisné chyby v editore. Vypnuté = bez kontroly.'],
                    ['key' => 'tabSize', 'type' => 'int', 'label' => 'Veľkosť tabulátora', 'default' => 2, 'rules' => ['required', 'int', 'min:2', 'max:8']],
                    ['key' => 'customComponentsEnabled', 'type' => 'bool', 'label' => 'Povoliť custom komponenty editora', 'default' => false, 'rules' => ['bool'], 'help' => 'Pluginy môžu registrovať vlastné bloky pre Markdown a WYSIWYG (It.60).'],
                    ['key' => 'profileCustomComponents', 'type' => 'string', 'label' => 'Custom komponenty podľa profilu (JSON)', 'default' => '{}', 'rules' => ['string'], 'help' => 'Mapa profil → zoznam ID komponentov. Upravuje sa v paneli nižšie.'],
                ],
            ],
            'navigationUi' => [
                'label' => 'Navigácia (UI)',
                'fields' => [
                    ['key' => 'defaultPreviewScale', 'type' => 'int', 'label' => 'Predvolená mierka hover náhľadu (×10)', 'default' => 15, 'rules' => ['required', 'int', 'min:10', 'max:30'], 'help' => 'Hodnota 15 = mierka 1.5×. It.56.'],
                    ['key' => 'maxTooltipWidthPx', 'type' => 'int', 'label' => 'Max. šírka tooltipu (px)', 'default' => 280, 'rules' => ['required', 'int', 'min:160', 'max:480'], 'help' => 'Obmedzenie šírky hover náhľadu v menu.'],
                    ['key' => 'enableHoverAnimations', 'type' => 'bool', 'label' => 'Animácie hover náhľadu', 'default' => true, 'rules' => ['bool'], 'help' => 'Respektuje prefers-reduced-motion — pri vypnutí animácií sa zobrazí statický náhľad.'],
                ],
            ],
            'smtp' => [
                'label' => 'Email / SMTP',
                'fields' => [
                    ['key' => 'enabled', 'type' => 'bool', 'label' => 'Enable SMTP', 'default' => false, 'rules' => ['bool'], 'help' => 'Zapnuté = systém odosiela e-maily (OTP, notifikácie, kontakt). Vypnuté = odosielanie vypnuté.'],
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
                    ['key' => 'toastEnabled', 'type' => 'bool', 'label' => 'Enable toast notifications', 'default' => true, 'rules' => ['bool'], 'help' => 'Zapnuté = krátke hlášky pri ukladaní a chybách v admin rozhraní. Vypnuté = bez toastov.'],
                    ['key' => 'toastPosition', 'type' => 'enum', 'label' => 'Toast position', 'default' => 'top-right', 'options' => ['top-right', 'top-left', 'bottom-right', 'bottom-left'], 'rules' => ['required', 'in:top-right,top-left,bottom-right,bottom-left']],
                    ['key' => 'toastDuration', 'type' => 'int', 'label' => 'Default duration (ms)', 'default' => 3000, 'rules' => ['required', 'int', 'min:1000', 'max:30000']],
                    ['key' => 'toastDebugMode', 'type' => 'bool', 'label' => 'Debug mode (longer toasts, console log)', 'default' => false, 'rules' => ['bool'], 'help' => 'Useful when developing modules, code editor, and content workflows.'],
                ],
            ],
            'connectors' => [
                'label' => 'Notification connectors',
                'fields' => [
                    ['key' => 'emailEnabled', 'type' => 'bool', 'label' => 'Email channel (SMTP)', 'default' => false, 'rules' => ['bool'], 'help' => 'Zapnuté = incidenty a reporty môžu ísť e-mailom (vyžaduje zapnuté SMTP). Vypnuté = kanál e-mail sa nepoužije.'],
                    ['key' => 'ntfyEnabled', 'type' => 'bool', 'label' => 'Enable ntfy', 'default' => false, 'rules' => ['bool'], 'help' => 'Zapnuté = push notifikácie cez ntfy. Vypnuté = kanál ntfy vypnutý.'],
                    ['key' => 'ntfyServer', 'type' => 'url', 'label' => 'ntfy server URL', 'default' => 'https://ntfy.sh', 'rules' => ['url', 'max:255']],
                    ['key' => 'ntfyTopic', 'type' => 'string', 'label' => 'ntfy topic', 'default' => '', 'rules' => ['string', 'max:120']],
                    ['key' => 'ntfyAuthMode', 'type' => 'enum', 'label' => 'ntfy authentication', 'default' => 'none', 'options' => ['none', 'token', 'basic'], 'rules' => ['required', 'in:none,token,basic'], 'help' => 'Use token for ntfy.sh ACL topics or Basic for self-hosted instances.'],
                    ['key' => 'ntfyAccessToken', 'type' => 'password', 'label' => 'ntfy access token', 'default' => '', 'rules' => ['string', 'max:512']],
                    ['key' => 'ntfyUsername', 'type' => 'string', 'label' => 'ntfy username (Basic auth)', 'default' => '', 'rules' => ['string', 'max:120']],
                    ['key' => 'ntfyPassword', 'type' => 'password', 'label' => 'ntfy password (Basic auth)', 'default' => '', 'rules' => ['string', 'max:255']],
                    ['key' => 'discordEnabled', 'type' => 'bool', 'label' => 'Enable Discord webhook', 'default' => false, 'rules' => ['bool'], 'help' => 'Zapnuté = incidenty sa posielajú na Discord webhook. Vypnuté = webhook sa nevolá.'],
                    ['key' => 'discordWebhookUrl', 'type' => 'url', 'label' => 'Discord webhook URL', 'default' => '', 'rules' => ['url', 'max:512']],
                    ['key' => 'telegramEnabled', 'type' => 'bool', 'label' => 'Enable Telegram bot', 'default' => false, 'rules' => ['bool'], 'help' => 'Zapnuté = incidenty do Telegram chatu. Vypnuté = bot sa nepoužije.'],
                    ['key' => 'telegramBotToken', 'type' => 'password', 'label' => 'Telegram bot token', 'default' => '', 'rules' => ['string', 'max:255']],
                    ['key' => 'telegramChatId', 'type' => 'string', 'label' => 'Telegram chat ID', 'default' => '', 'rules' => ['string', 'max:64']],
                    ['key' => 'webhookEnabled', 'type' => 'bool', 'label' => 'Enable generic webhook', 'default' => false, 'rules' => ['bool'], 'help' => 'Zapnuté = incidenty na vlastný HTTP webhook. Vypnuté = webhook vypnutý.'],
                    ['key' => 'webhookUrl', 'type' => 'url', 'label' => 'Webhook URL', 'default' => '', 'rules' => ['url', 'max:512']],
                    ['key' => 'webhookSecret', 'type' => 'password', 'label' => 'Webhook secret (optional)', 'default' => '', 'rules' => ['string', 'max:255']],
                    ['key' => 'webhookAuthHeader', 'type' => 'string', 'label' => 'Webhook auth header name', 'default' => 'X-Webhook-Secret', 'rules' => ['string', 'max:120'], 'help' => 'HTTP header used to send webhookSecret when set.'],
                ],
            ],
            'monitoring' => [
                'label' => 'Monitoring & incidents',
                'fields' => [
                    ['key' => 'alertsEnabled', 'type' => 'bool', 'label' => 'Enable incident alerts', 'default' => false, 'rules' => ['bool'], 'help' => 'Zapnuté = systém posiela alerty pri incidentoch cez zapnuté kanály. Vypnuté = žiadne automatické alerty.'],
                    ['key' => 'alertEmail', 'type' => 'email', 'label' => 'Fallback alert email', 'default' => '', 'rules' => ['email', 'max:255'], 'help' => 'Used when connectors are off; defaults to admin email.'],
                    ['key' => 'notifyFailedLogin', 'type' => 'bool', 'label' => 'Alert on failed login', 'default' => true, 'rules' => ['bool'], 'help' => 'Zapnuté = upozornenie pri neúspešnom prihlásení. Vypnuté = udalosť sa nehlási.'],
                    ['key' => 'notifySecurityIncident', 'type' => 'bool', 'label' => 'Alert on security audit events', 'default' => true, 'rules' => ['bool'], 'help' => 'Zapnuté = upozornenie pri bezpečnostných audit udalostiach. Vypnuté = neposiela sa.'],
                    ['key' => 'notifyTrafficSpike', 'type' => 'bool', 'label' => 'Alert on traffic spike', 'default' => false, 'rules' => ['bool'], 'help' => 'Zapnuté = alert pri prekročení prahu návštevnosti. Vypnuté = traffic spike sa ignoruje.'],
                    ['key' => 'trafficSpikeThreshold', 'type' => 'int', 'label' => 'Traffic spike threshold (visits/hour)', 'default' => 500, 'rules' => ['int', 'min:10', 'max:100000']],
                    ['key' => 'minSeverity', 'type' => 'enum', 'label' => 'Minimum audit severity', 'default' => 'warning', 'options' => ['info', 'warning', 'error', 'critical'], 'rules' => ['required', 'in:info,warning,error,critical']],
                    ['key' => 'reportsEnabled', 'type' => 'bool', 'label' => 'Enable scheduled monitoring reports', 'default' => false, 'rules' => ['bool'], 'help' => 'Requires cron: php backend/bin/console monitoring:run-schedule'],
                    ['key' => 'reportInterval', 'type' => 'enum', 'label' => 'Report interval', 'default' => 'day', 'options' => ['hour', 'day', 'week'], 'rules' => ['required', 'in:hour,day,week']],
                    ['key' => 'reportTime', 'type' => 'string', 'label' => 'Send time (HH:MM)', 'default' => '08:00', 'rules' => ['required', 'string', 'max:5'], 'help' => 'Used for daily and weekly reports (site timezone).'],
                    ['key' => 'reportWeekday', 'type' => 'enum', 'label' => 'Weekly report day', 'default' => 'mon', 'options' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'], 'rules' => ['required', 'in:mon,tue,wed,thu,fri,sat,sun']],
                    ['key' => 'reportMinute', 'type' => 'int', 'label' => 'Hourly report minute (0–59)', 'default' => 0, 'rules' => ['int', 'min:0', 'max:59'], 'help' => 'For hourly interval – minute past each hour.'],
                    ['key' => 'reportConnector', 'type' => 'enum', 'label' => 'Report connector', 'default' => 'email', 'options' => ['email', 'ntfy', 'discord', 'telegram', 'webhook', 'all'], 'rules' => ['required', 'in:email,ntfy,discord,telegram,webhook,all']],
                    ['key' => 'reportIncludeAnalytics', 'type' => 'bool', 'label' => 'Report: analytics stats', 'default' => true, 'rules' => ['bool'], 'help' => 'Zapnuté = report obsahuje štatistiky návštevnosti. Vypnuté = bez analytiky.'],
                    ['key' => 'reportIncludeHealth', 'type' => 'bool', 'label' => 'Report: system health', 'default' => true, 'rules' => ['bool'], 'help' => 'Zapnuté = report obsahuje stav zdravia systému. Vypnuté = bez health sekcie.'],
                    ['key' => 'reportIncludeFlatFile', 'type' => 'bool', 'label' => 'Report: flat-file counts', 'default' => true, 'rules' => ['bool'], 'help' => 'Zapnuté = report obsahuje počty stránok/článkov. Vypnuté = bez štatistík obsahu.'],
                    ['key' => 'notifyLogErrors', 'type' => 'bool', 'label' => 'Alert on log ERROR/CRITICAL', 'default' => true, 'rules' => ['bool'], 'help' => 'Zapnuté = ERROR/CRITICAL v logu spustí alert. Vypnuté = chyby v logu sa nehlásia.'],
                    ['key' => 'notifyLogWarnings', 'type' => 'bool', 'label' => 'Alert on log WARNING', 'default' => false, 'rules' => ['bool'], 'help' => 'Zapnuté = WARNING v logu spustí alert. Vypnuté = varovania sa nehlásia.'],
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
                    ['key' => 'enabled', 'type' => 'bool', 'label' => 'Enable code policy checks (core)', 'default' => true, 'rules' => ['bool'], 'help' => 'Core Code Editor writes. Untrusted paths (plugins, themes, layout shortcodes, Monaco validateUntrusted) are ALWAYS checked even when this is off.'],
                    ['key' => 'strictMode', 'type' => 'bool', 'label' => 'Strict extension namespace rules', 'default' => true, 'rules' => ['bool'], 'help' => 'Zapnuté = prísnejšie namespaces pre Extensions. Untrusted PHP vždy vyžaduje strict_types.'],
                    ['key' => 'maxFileSizeKb', 'type' => 'int', 'label' => 'Max file size (KB)', 'default' => 512, 'rules' => ['required', 'int', 'min:16', 'max:4096']],
                    ['key' => 'untrustedMaxFileSizeKb', 'type' => 'int', 'label' => 'Max untrusted file size (KB)', 'default' => 256, 'rules' => ['required', 'int', 'min:16', 'max:1024'], 'help' => 'Cap for plugins/themes/layout shortcode artifacts (cannot exceed maxFileSizeKb).'],
                    ['key' => 'forbiddenPhpFunctions', 'type' => 'text', 'label' => 'Forbidden PHP functions', 'default' => 'eval,exec,shell_exec,system,passthru,proc_open,popen,assert,create_function', 'rules' => ['string', 'max:2000'], 'help' => 'Comma-separated list scanned before save. Untrusted trees also block include/require/unserialize/call_user_func*.'],
                ],
            ],
            'engine' => [
                'label' => 'Hybrid Engine',
                'superAdminOnly' => true,
                'fields' => [
                    ['key' => 'deploymentMode', 'type' => 'enum', 'label' => 'Deployment mode', 'default' => 'classic', 'options' => ['classic', 'hybrid', 'git_headless'], 'rules' => ['required', 'in:classic'], 'help' => 'Iteration 68: only Classic is active. Hybrid and Git headless appear as not installed.'],
                    ['key' => 'storageDriver', 'type' => 'enum', 'label' => 'Storage driver', 'default' => 'local', 'options' => ['local'], 'rules' => ['required', 'in:local'], 'help' => 'Local flat-file driver (default). Remote drivers require later iterations.'],
                    ['key' => 'schemaValidationEnabled', 'type' => 'bool', 'label' => 'Enable JSON Schema validation', 'default' => true, 'rules' => ['bool'], 'help' => 'When enabled, admin JSON documents are validated against registered schemas before write.'],
                    ['key' => 'capabilityProbeEnabled', 'type' => 'bool', 'label' => 'Enable capability probe', 'default' => true, 'rules' => ['bool'], 'help' => 'Expose engine capability diagnostics in admin settings.'],
                    ['key' => 'cacheDriver', 'type' => 'enum', 'label' => 'Cache driver', 'default' => 'auto', 'options' => ['auto', 'memory', 'file', 'redis'], 'rules' => ['required', 'in:auto,memory,file'], 'help' => 'Iteration 69: auto = memory + file chain. Redis appears as not installed.'],
                    ['key' => 'cacheDefaultTtlSeconds', 'type' => 'int', 'label' => 'Default cache TTL (seconds)', 'default' => 300, 'rules' => ['required', 'int', 'min:60', 'max:86400']],
                    ['key' => 'httpValidatorsEnabled', 'type' => 'bool', 'label' => 'Enable HTTP ETag / Last-Modified', 'default' => true, 'rules' => ['bool'], 'help' => 'Conditional requests on safe public GET endpoints (e.g. /api/settings/public).'],
                    ['key' => 'gitEnabled', 'type' => 'bool', 'label' => 'Enable Git publish distribution', 'default' => false, 'rules' => ['bool'], 'help' => 'Iteration 70: Git is distribution only; SSOT stays on disk. Default off (Classic).'],
                    ['key' => 'gitPublishStrategy', 'type' => 'enum', 'label' => 'Git publish strategy', 'default' => 'disabled', 'options' => ['disabled', 'immediate', 'queued'], 'rules' => ['required', 'in:disabled,immediate,queued'], 'help' => 'disabled = no Git calls; immediate = commit per content write; queued = batch release commit.'],
                    ['key' => 'gitPublisher', 'type' => 'enum', 'label' => 'Git publisher driver', 'default' => 'local', 'options' => ['local', 'github_api'], 'rules' => ['required', 'in:local'], 'help' => 'local = server git binary. github_api deferred in this release.'],
                    ['key' => 'gitRepositoryPath', 'type' => 'text', 'label' => 'Git repository path', 'default' => '', 'rules' => ['string', 'max:500'], 'help' => 'Absolute server path to a Git working tree containing pages/ and blog/ content. Never exposed to the frontend.'],
                    ['key' => 'gitRemote', 'type' => 'string', 'label' => 'Git remote name', 'default' => 'origin', 'rules' => ['string', 'max:100'], 'help' => 'Allow-listed remote name (e.g. origin).'],
                    ['key' => 'gitBranch', 'type' => 'string', 'label' => 'Git branch', 'default' => 'main', 'rules' => ['string', 'max:100'], 'help' => 'Allow-listed branch name for optional push.'],
                    ['key' => 'gitPushEnabled', 'type' => 'bool', 'label' => 'Push after commit', 'default' => false, 'rules' => ['bool'], 'help' => 'When enabled, successful commits attempt git push to configured remote/branch.'],
                    ['key' => 'gitCommitMessageTemplate', 'type' => 'string', 'label' => 'Commit message template', 'default' => 'content: publish {count} change(s)', 'rules' => ['required', 'string', 'max:200'], 'help' => 'Use {count} placeholder for number of staged files.'],
                    ['key' => 'performanceGuardEnabled', 'type' => 'bool', 'label' => 'Enable Performance Guard (APM)', 'default' => false, 'rules' => ['bool'], 'help' => 'Iteration 71: lightweight in-request latency and I/O sampling. Disabled by default.'],
                    ['key' => 'performanceGuardSampleRate', 'type' => 'float', 'label' => 'APM sample rate', 'default' => 1.0, 'rules' => ['number', 'min:0', 'max:1'], 'help' => '1.0 = every request when enabled; lower values reduce overhead.'],
                    ['key' => 'performanceGuardLatencyMsWarning', 'type' => 'int', 'label' => 'Latency warning (ms)', 'default' => 200, 'rules' => ['required', 'int', 'min:50', 'max:60000']],
                    ['key' => 'performanceGuardLatencyMsCritical', 'type' => 'int', 'label' => 'Latency critical (ms)', 'default' => 500, 'rules' => ['required', 'int', 'min:100', 'max:120000']],
                    ['key' => 'performanceGuardBreachCount', 'type' => 'int', 'label' => 'Breaches before incident', 'default' => 3, 'rules' => ['required', 'int', 'min:1', 'max:100']],
                    ['key' => 'performanceGuardWindowMinutes', 'type' => 'int', 'label' => 'Breach window (minutes)', 'default' => 10, 'rules' => ['required', 'int', 'min:1', 'max:1440']],
                    ['key' => 'performanceGuardRemediationMode', 'type' => 'enum', 'label' => 'Remediation mode', 'default' => 'suggest', 'options' => ['off', 'suggest', 'automatic'], 'rules' => ['required', 'in:off,suggest,automatic'], 'help' => 'suggest = incidents only; automatic = allow-listed cache purge after capability probe (never enables Redis).'],
                ],
            ],
            'comments' => [
                'label' => 'Komentáre',
                'fields' => [
                    ['key' => 'enabled', 'type' => 'bool', 'label' => 'Povoliť komentáre globálne', 'default' => true, 'rules' => ['bool'], 'help' => 'Vypnutím sa skryje formulár na celom webe (okrem článkov s vlastným prepínačom).'],
                    ['key' => 'requireApproval', 'type' => 'bool', 'label' => 'Globálne vyžadovať schválenie', 'default' => true, 'rules' => ['bool'], 'help' => 'Nové komentáre čakajú na schválenie v administrácii. Dá sa prepísať pri jednotlivom článku.'],
                    ['key' => 'allowGuestComments', 'type' => 'bool', 'label' => 'Povoliť komentáre od hostí', 'default' => true, 'rules' => ['bool'], 'help' => 'Neprihlásení návštevníci môžu pridávať komentáre. Dá sa prepísať pri jednotlivom článku.'],
                    ['key' => 'maxLength', 'type' => 'int', 'label' => 'Max. dĺžka komentára', 'default' => 2000, 'rules' => ['required', 'int', 'min:50', 'max:5000']],
                    ['key' => 'spamHeuristicsEnabled', 'type' => 'bool', 'label' => 'Spam heuristika', 'default' => true, 'rules' => ['bool'], 'help' => 'Honeypot + skóre (linky, disposable e-mail, rýchlosť). Vypnutím zostáva len honeypot.'],
                    ['key' => 'spamMaxLinks', 'type' => 'int', 'label' => 'Spam: max. linkov v texte', 'default' => 2, 'rules' => ['int', 'min:0', 'max:20']],
                    ['key' => 'spamVelocityMaxPerHour', 'type' => 'int', 'label' => 'Spam: max. komentárov / IP / hod', 'default' => 5, 'rules' => ['int', 'min:1', 'max:100']],
                    ['key' => 'spamQuarantineThreshold', 'type' => 'int', 'label' => 'Spam: hranica karantény (skóre)', 'default' => 50, 'rules' => ['int', 'min:1', 'max:200']],
                    ['key' => 'spamRejectThreshold', 'type' => 'int', 'label' => 'Spam: hranica odmietnutia (skóre)', 'default' => 80, 'rules' => ['int', 'min:2', 'max:300']],
                ],
            ],
            'maintenance' => [
                'label' => 'Režim údržby',
                'fields' => [
                    ['key' => 'mode', 'type' => 'enum', 'label' => 'Aktívny režim', 'default' => 'off', 'options' => ['off', 'coming_soon', 'under_maintenance'], 'rules' => ['required', 'in:off,coming_soon,under_maintenance'], 'help' => 'Naraz môže byť zapnutý iba jeden režim.'],
                    ['key' => 'heroImageUrl', 'type' => 'url', 'label' => 'Pozadie (URL)', 'default' => '', 'rules' => ['string', 'max:2000'], 'help' => 'Absolútna URL alebo cesta /storage/… — voliteľný obrázok na pozadí oboch stránok údržby. V administrácii je možné vybrať z médií alebo nahrať súbor z disku.'],
                    ['key' => 'newsletterEnabled', 'type' => 'bool', 'label' => 'Povoliť newsletter', 'default' => true, 'rules' => ['bool'], 'help' => 'Zapnuté = na Coming Soon stránke sa zobrazí prihlásenie na newsletter. Vypnuté = formulár skrytý.'],
                    ['key' => 'newsletterHint', 'type' => 'text', 'label' => 'Text newsletteru', 'default' => 'Prihláste sa na odber noviniek a dáme vám vedieť hneď po spustení.', 'rules' => ['string', 'max:500']],
                    ['key' => 'comingSoonBadge', 'type' => 'string', 'label' => 'Coming Soon – odznak', 'default' => 'Pripravujeme', 'rules' => ['string', 'max:80']],
                    ['key' => 'comingSoonTitle', 'type' => 'string', 'label' => 'Coming Soon – nadpis', 'default' => 'Už čoskoro', 'rules' => ['required', 'string', 'min:2', 'max:120']],
                    ['key' => 'comingSoonSubtitle', 'type' => 'text', 'label' => 'Coming Soon – podnadpis', 'default' => 'Pracujeme na niečom výnimočnom. Stránka bude čoskoro online.', 'rules' => ['string', 'max:500']],
                    ['key' => 'comingSoonBody', 'type' => 'text', 'label' => 'Coming Soon – telo', 'default' => '', 'rules' => ['string', 'max:3000']],
                    ['key' => 'maintenanceBadge', 'type' => 'string', 'label' => 'Údržba – odznak', 'default' => 'Údržba', 'rules' => ['string', 'max:80']],
                    ['key' => 'maintenanceTitle', 'type' => 'string', 'label' => 'Údržba – nadpis', 'default' => 'Momentálne prebieha údržba', 'rules' => ['required', 'string', 'min:2', 'max:120']],
                    ['key' => 'maintenanceSubtitle', 'type' => 'text', 'label' => 'Údržba – podnadpis', 'default' => 'Pracujeme na vylepšeniach. Skúste to prosím neskôr.', 'rules' => ['string', 'max:500']],
                    ['key' => 'maintenanceBody', 'type' => 'text', 'label' => 'Údržba – telo', 'default' => '', 'rules' => ['string', 'max:3000']],
                    ['key' => 'maintenanceShowContactForm', 'type' => 'bool', 'label' => 'Zobraziť kontaktný formulár', 'default' => true, 'rules' => ['bool'], 'help' => 'Zapnuté = počas údržby je dostupný kontaktný formulár. Vypnuté = len informačný text.'],
                    ['key' => 'maintenanceContactSubject', 'type' => 'string', 'label' => 'Predmet správy z údržby', 'default' => 'Správa z režimu údržby', 'rules' => ['string', 'max:200']],
                ],
            ],
            'contact' => [
                'label' => 'Kontaktný formulár',
                'fields' => [
                    ['key' => 'subjects', 'type' => 'text', 'label' => 'Predvolené predmety správ', 'default' => "Všeobecný dotaz\nTechnická podpora\nObchodná spolupráca\nInformácie o produkte", 'rules' => ['required', 'string', 'max:2000'], 'help' => 'Jeden predmet na riadok — zobrazí sa vo verejnom kontaktnom formulári.'],
                    ['key' => 'allowCustomSubject', 'type' => 'bool', 'label' => 'Povoliť vlastný predmet', 'default' => true, 'rules' => ['bool'], 'help' => 'Návštevník môže zvoliť „Vlastný predmet“ a napísať vlastný text.'],
                ],
            ],
            'newsletter' => [
                'label' => 'Newsletter',
                'fields' => [
                    ['key' => 'footerEnabled', 'type' => 'bool', 'label' => 'Povoliť newsletter vo footeri', 'default' => false, 'rules' => ['bool'], 'help' => 'Zapnuté = kompaktný odberový formulár v pätičke verejného webu. Vypnuté = formulár skrytý.'],
                    ['key' => 'footerHint', 'type' => 'text', 'label' => 'Text vo footeri', 'default' => 'Prihláste sa na odber noviniek a nezmeškajte novinky.', 'rules' => ['string', 'max:500'], 'help' => 'Krátky popis pod nadpisom newsletteru vo footeri.'],
                    ['key' => 'fromEmail', 'type' => 'email', 'label' => 'Odosielateľ (e-mail)', 'default' => '', 'rules' => ['email', 'max:200'], 'help' => 'Pripravené pre budúce odosielanie; fallback na SMTP nastavenia.'],
                    ['key' => 'fromName', 'type' => 'string', 'label' => 'Odosielateľ (meno)', 'default' => '', 'rules' => ['string', 'max:120'], 'help' => 'Zobrazované meno odosielateľa v budúcich e-mailoch.'],
                    ['key' => 'replyTo', 'type' => 'email', 'label' => 'Reply-To', 'default' => '', 'rules' => ['email', 'max:200'], 'help' => 'Voliteľná adresa pre odpovede na newsletter.'],
                    ['key' => 'enabledPreferences', 'type' => 'text', 'label' => 'Typy odberu vo formulári', 'default' => "weekly_digest\ngeneral_news", 'rules' => ['string', 'max:500'], 'help' => 'Jeden kľúč na riadok: weekly_digest, new_article, cms_release, general_news.'],
                    ['key' => 'requireConsentCheckbox', 'type' => 'bool', 'label' => 'Vyžadovať súhlas (checkbox)', 'default' => false, 'rules' => ['bool'], 'help' => 'Zapnuté = návštevník musí explicitne potvrdiť súhlas pred odberom.'],
                    ['key' => 'sendEnabled', 'type' => 'bool', 'label' => 'Povoliť odosielanie e-mailov', 'default' => false, 'rules' => ['bool'], 'help' => 'Master prepínač pre weekly digest a notifikácie o nových článkoch. Vyžaduje nakonfigurovaný SMTP / e-mail kanál.'],
                    ['key' => 'weeklyDigestEnabled', 'type' => 'bool', 'label' => 'Týždenný digest', 'default' => false, 'rules' => ['bool'], 'help' => 'Odosiela zhrnutie publikovaných článkov odberateľom s preferenciou weekly_digest.'],
                    ['key' => 'newArticleEnabled', 'type' => 'bool', 'label' => 'Notifikácia pri novom článku', 'default' => false, 'rules' => ['bool'], 'help' => 'Pri publikovaní článku odošle e-mail odberateľom s preferenciou new_article.'],
                    ['key' => 'cmsReleaseEnabled', 'type' => 'bool', 'label' => 'Kampane o vydaniach CMS', 'default' => false, 'rules' => ['bool'], 'help' => 'Povolí manuálne odoslanie oznámenia o verzii odberateľom s preferenciou cms_release.'],
                    ['key' => 'instantArticleCooldownHours', 'type' => 'int', 'label' => 'Cooldown medzi instant mailmi (hodiny)', 'default' => 24, 'rules' => ['int', 'min:1', 'max:168'], 'help' => 'Max. jeden instant mail na odberateľa za dané obdobie.'],
                    ['key' => 'sendBatchLimitPerRun', 'type' => 'int', 'label' => 'Limit odoslaní na beh', 'default' => 50, 'rules' => ['int', 'min:1', 'max:500'], 'help' => 'Počet e-mailov odoslaných v jednom behu (cron alebo manuálne).'],
                    ['key' => 'requireDoubleOptIn', 'type' => 'bool', 'label' => 'Double opt-in (potvrdenie e-mailom)', 'default' => false, 'rules' => ['bool'], 'help' => 'Nový odberateľ je pending, kým neklikne na potvrdzovací link v e-maili.'],
                    ['key' => 'confirmTokenTtlHours', 'type' => 'int', 'label' => 'Platnosť potvrdzovacieho linku (hodiny)', 'default' => 72, 'rules' => ['int', 'min:1', 'max:168'], 'help' => 'Po uplynutí musí odberateľ požiadať o nový potvrdzovací e-mail.'],
                ],
            ],
            'marketing' => [
                'label' => 'Marketing & sociálne siete',
                'fields' => [
                    ['key' => 'demoFooterLinkEnabled', 'type' => 'bool', 'label' => 'Zobraziť odkaz na demo vo footeri', 'default' => true, 'rules' => ['bool'], 'help' => 'Platí len na produkčnej inštancii (DEMO_MODE=false). Na demo subdoméne sa nezobrazuje duplicitný odkaz.'],
                    ['key' => 'demoUrl', 'type' => 'url', 'label' => 'URL demo inštancie', 'default' => 'https://demo.paginiumcms.com', 'rules' => ['string', 'max:500'], 'help' => 'Cieľ footer odkazu „Vyskúšajte CMS“. Fallback: env DEMO_PUBLIC_URL.'],
                    ['key' => 'socialLinksEnabled', 'type' => 'bool', 'label' => 'Zobraziť sociálne siete vo footeri', 'default' => true, 'rules' => ['bool'], 'help' => 'Ikony s odkazmi (GitHub, X, …) v spodnej časti verejného webu.'],
                    ['key' => 'socialLinksJson', 'type' => 'text', 'label' => 'Sociálne siete (JSON)', 'default' => '', 'rules' => ['string', 'max:8000'], 'help' => 'Spravované vizuálnym editorom nižšie. Ukladá sa normalizovaný JSON.'],
                ],
            ],
            'gallery' => [
                'label' => 'Feature gallery',
                'fields' => [
                    ['key' => 'enabled', 'type' => 'bool', 'label' => 'Zapnúť galériu na verejnom webe', 'default' => false, 'rules' => ['bool'], 'help' => 'Master prepínač pre sekciu so screenshotmi administrácie.'],
                    ['key' => 'placement', 'type' => 'enum', 'label' => 'Umiestnenie', 'default' => 'route', 'options' => ['home', 'route', 'both', 'off'], 'rules' => ['required', 'in:home,route,both,off'], 'help' => 'Kde sa galéria zobrazí: domovská stránka, samostatná route, oboje, alebo vypnuté.'],
                    ['key' => 'publicRoute', 'type' => 'string', 'label' => 'Verejná route', 'default' => '/features', 'rules' => ['required', 'string', 'max:120'], 'help' => 'Jednosegmentová cesta bez domény, napr. /features alebo /funkcie.'],
                    ['key' => 'layout', 'type' => 'enum', 'label' => 'Layout', 'default' => 'grid', 'options' => ['grid', 'slider', 'hero-strip'], 'rules' => ['required', 'in:grid,slider,hero-strip'], 'help' => 'Grid = dlaždice; slider = carousel s autoplay; hero-strip = široký pás screenshotov.'],
                    ['key' => 'effectPreset', 'type' => 'enum', 'label' => 'Efekt (preset)', 'default' => 'subtle', 'options' => ['subtle', 'cinematic', 'minimal'], 'rules' => ['required', 'in:subtle,cinematic,minimal'], 'help' => 'subtle = fade+scale; cinematic = crossfade+vignette; minimal = okamžitá výmena (bez animácie).'],
                    ['key' => 'autoplayEnabled', 'type' => 'bool', 'label' => 'Autoplay slidera', 'default' => true, 'rules' => ['bool'], 'help' => 'Platí pre layout slider a hero-strip. Pauza pri hover/focus; vypnuté pri prefers-reduced-motion.'],
                    ['key' => 'autoplayIntervalMs', 'type' => 'int', 'label' => 'Autoplay interval (ms)', 'default' => 6000, 'rules' => ['required', 'int', 'min:4000', 'max:15000'], 'help' => 'Interval medzi slidmi (4000–15000 ms).'],
                    ['key' => 'showFeatureTags', 'type' => 'bool', 'label' => 'Zobraziť tagy modulov', 'default' => true, 'rules' => ['bool'], 'help' => 'Badge s názvom modulu (Analytics, Newsletter, …) pri položkách galérie.'],
                    ['key' => 'modalCaptionStyle', 'type' => 'enum', 'label' => 'Štýl popisu v modale', 'default' => 'below', 'options' => ['below', 'overlay', 'side'], 'rules' => ['required', 'in:below,overlay,side'], 'help' => 'below = pod obrázkom; overlay = cez spodok; side = vedľa (široké obrazovky).'],
                ],
            ],
            'company' => [
                'label' => 'Firemné údaje',
                'fields' => [
                    ['key' => 'showOnContactPage', 'type' => 'bool', 'label' => 'Zobraziť blok na kontaktnej stránke', 'default' => true, 'rules' => ['bool'], 'help' => 'Zapnuté = firemné údaje sa zobrazia na kontaktnej stránke. Vypnuté = blok skrytý.'],
                    ['key' => 'name', 'type' => 'string', 'label' => 'Názov firmy', 'default' => '', 'rules' => ['string', 'max:200']],
                    ['key' => 'legalName', 'type' => 'string', 'label' => 'Právna forma / obchodné meno', 'default' => '', 'rules' => ['string', 'max:200']],
                    ['key' => 'ico', 'type' => 'string', 'label' => 'IČO', 'default' => '', 'rules' => ['string', 'max:20']],
                    ['key' => 'dic', 'type' => 'string', 'label' => 'DIČ', 'default' => '', 'rules' => ['string', 'max:20']],
                    ['key' => 'icDph', 'type' => 'string', 'label' => 'IČ DPH', 'default' => '', 'rules' => ['string', 'max:20']],
                    ['key' => 'address', 'type' => 'text', 'label' => 'Adresa', 'default' => '', 'rules' => ['string', 'max:500']],
                    ['key' => 'email', 'type' => 'email', 'label' => 'Kontaktný e-mail', 'default' => '', 'rules' => ['email', 'max:200']],
                    ['key' => 'phone', 'type' => 'string', 'label' => 'Telefón', 'default' => '', 'rules' => ['string', 'max:40']],
                    ['key' => 'website', 'type' => 'url', 'label' => 'Web', 'default' => '', 'rules' => ['url', 'max:500']],
                    ['key' => 'mapEmbedUrl', 'type' => 'url', 'label' => 'Google Maps embed URL', 'default' => '', 'rules' => ['url', 'max:2000'], 'help' => 'Google Maps → Zdieľať → Vložiť mapu (hodnota src z iframe).'],
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
            'login' => [
                'label' => 'Prihlásenie a registrácia',
                'fields' => [
                    ['key' => 'pageTitle', 'type' => 'string', 'label' => 'Nadpis prihlasovacej stránky', 'default' => '', 'rules' => ['string', 'max:120'], 'help' => 'Prázdne = použije sa názov stránky z Všeobecných.'],
                    ['key' => 'pageDescription', 'type' => 'text', 'label' => 'Popis prihlasovacej stránky', 'default' => '', 'rules' => ['string', 'max:500'], 'help' => 'Krátky text v informačnom paneli prihlásenia/registrácie.'],
                    ['key' => 'backgroundImageUrl', 'type' => 'url', 'label' => 'URL obrázka pozadia', 'default' => '', 'rules' => ['string', 'max:512'], 'help' => 'Absolútna URL alebo cesta /storage/… — zobrazí sa za prihlasovacím formulárom. V administrácii je možné vybrať z médií alebo nahrať súbor z disku.'],
                    ['key' => 'infoBullets', 'type' => 'text', 'label' => 'Informačné body', 'default' => "Bezpečné prihlásenie do administrácie\nSpráva stránok, článkov a médií\nFlat-file úložisko bez SQL databázy", 'rules' => ['string', 'max:2000'], 'help' => 'Jeden riadok = jeden bod v informačnom paneli.'],
                ],
            ],
            'privacy' => [
                'label' => 'Súkromie a cookies',
                'fields' => [
                    ['key' => 'cookieBannerEnabled', 'type' => 'bool', 'label' => 'Zobraziť cookie lištu', 'default' => false, 'rules' => ['bool'], 'help' => 'Zapnuté = pri prvej návšteve sa zobrazí súhlas s cookies (GDPR).'],
                    ['key' => 'cookieBannerText', 'type' => 'text', 'label' => 'Text cookie lišty', 'default' => 'Tento web používa cookies na zabezpečenie funkčnosti a zlepšenie používateľského zážitku. Môžete prijať všetky, odmietnuť voliteľné alebo upraviť nastavenia.', 'rules' => ['string', 'max:1000']],
                    ['key' => 'cookiePolicyUrl', 'type' => 'url', 'label' => 'URL zásad cookies / GDPR', 'default' => '', 'rules' => ['string', 'max:500'], 'help' => 'Voliteľný odkaz na stránku so zásadami ochrany súkromia.'],
                    ['key' => 'cookieShowRejectButton', 'type' => 'bool', 'label' => 'Tlačidlo „Odmietnuť voliteľné“', 'default' => true, 'rules' => ['bool'], 'help' => 'Zapnuté = návštevník môže odmietnuť nevyhnutné cookies okrem technicky nutných.'],
                ],
            ],
            'security' => [
                'label' => 'Bezpečnosť',
                'fields' => [
                    ['key' => 'maxLoginAttempts', 'type' => 'int', 'label' => 'Max. neúspešných prihlásení', 'default' => 5, 'rules' => ['required', 'int', 'min:3', 'max:20'], 'help' => 'Po prekročení sa účet/IP dočasne zablokuje.'],
                    ['key' => 'lockoutMinutes', 'type' => 'int', 'label' => 'Dĺžka blokácie (min)', 'default' => 15, 'rules' => ['required', 'int', 'min:1', 'max:1440']],
                    ['key' => 'requireTwoFactorStaff', 'type' => 'bool', 'label' => 'Vynútiť 2FA pre editorov a adminov', 'default' => true, 'rules' => ['bool'], 'help' => 'Pri zapnutí nie je možné vypnúť 2FA pre roly EDITOR, ADMIN a SUPER_ADMIN.'],
                    ['key' => 'passwordMinLength', 'type' => 'int', 'label' => 'Min. dĺžka hesla', 'default' => 8, 'rules' => ['required', 'int', 'min:4', 'max:128'], 'help' => 'Platí pre registráciu, zmenu hesla a admin vytvorenie používateľa.'],
                    ['key' => 'passwordMaxLength', 'type' => 'int', 'label' => 'Max. dĺžka hesla', 'default' => 72, 'rules' => ['required', 'int', 'min:8', 'max:128']],
                    ['key' => 'passwordRequireUppercase', 'type' => 'bool', 'label' => 'Vyžadovať veľké písmeno (A–Z)', 'default' => true, 'rules' => ['bool']],
                    ['key' => 'passwordRequireLowercase', 'type' => 'bool', 'label' => 'Vyžadovať malé písmeno (a–z)', 'default' => true, 'rules' => ['bool']],
                    ['key' => 'passwordRequireNumbers', 'type' => 'bool', 'label' => 'Vyžadovať číslicu (0–9)', 'default' => true, 'rules' => ['bool']],
                    ['key' => 'passwordRequireSpecialChars', 'type' => 'bool', 'label' => 'Vyžadovať špeciálny znak', 'default' => true, 'rules' => ['bool'], 'help' => 'Napr. ! @ # $ % & *'],
                ],
            ],
            'contentSecurity' => [
                'label' => 'Bezpečnosť obsahu (XML/HTML)',
                'fields' => [
                    ['key' => 'sanitizeHtmlOnSave', 'type' => 'bool', 'label' => 'Sanitizovať HTML pri ukladaní', 'default' => true, 'rules' => ['bool'], 'help' => 'Odstráni nebezpečné tagy a atribúty z HTML/Tiptap výstupu.'],
                    ['key' => 'stripExternalEntities', 'type' => 'bool', 'label' => 'Blokovať externé XML entity', 'default' => true, 'rules' => ['bool'], 'help' => 'XXE ochrana pri parsovaní XML/SVG obsahu.'],
                    ['key' => 'allowSvgInline', 'type' => 'bool', 'label' => 'Povoliť inline SVG v obsahu', 'default' => false, 'rules' => ['bool'], 'help' => 'SVG môže obsahovať skript — odporúčame vypnuté.'],
                    ['key' => 'allowScriptTags', 'type' => 'bool', 'label' => 'Povoliť <script> v obsahu', 'default' => false, 'rules' => ['bool'], 'help' => 'Len pre dôveryhodných editorov; default off.'],
                    ['key' => 'allowedHtmlTags', 'type' => 'text', 'label' => 'Povolené HTML tagy', 'default' => 'p,h1,h2,h3,h4,ul,ol,li,a,strong,em,blockquote,code,pre,img,table,thead,tbody,tr,th,td', 'rules' => ['required', 'string', 'max:2000'], 'help' => 'Čiarkou oddelený whitelist tagov.'],
                ],
            ],
            'uploadSecurity' => [
                'label' => 'Bezpečnosť uploadu',
                'fields' => [
                    ['key' => 'scanMagicBytes', 'type' => 'bool', 'label' => 'Kontrolovať magic bytes súboru', 'default' => true, 'rules' => ['bool'], 'help' => 'Porovná hlavičku súboru s deklarovaným MIME typom.'],
                    ['key' => 'blockDoubleExtensions', 'type' => 'bool', 'label' => 'Blokovať dvojité prípony', 'default' => true, 'rules' => ['bool'], 'help' => 'Napr. shell.php.jpg — bežný upload útok.'],
                    ['key' => 'blockExecutables', 'type' => 'bool', 'label' => 'Blokovať spustiteľné prípony', 'default' => true, 'rules' => ['bool'], 'help' => 'php, exe, sh, bat, cmd, js v upload zložke.'],
                    ['key' => 'allowedExtensions', 'type' => 'text', 'label' => 'Povolené prípony', 'default' => 'jpg,jpeg,png,gif,webp,svg,pdf,md,txt', 'rules' => ['required', 'string', 'max:1000'], 'help' => 'Bez bodky, oddelené čiarkou.'],
                    ['key' => 'maxUploadSizeKb', 'type' => 'int', 'label' => 'Max. veľkosť uploadu (KB)', 'default' => 5120, 'rules' => ['required', 'int', 'min:64', 'max:51200'], 'help' => '5120 KB = 5 MB.'],
                    ['key' => 'allowedMimeTypes', 'type' => 'text', 'label' => 'Povolené MIME typy', 'default' => 'image/jpeg,image/png,image/gif,image/webp,image/svg+xml,application/pdf', 'rules' => ['required', 'string', 'max:2000'], 'help' => 'Oddeľte čiarkou.'],
                ],
            ],
            'accessControl' => [
                'label' => 'Oprávnenia rolí',
                'superAdminOnly' => true,
                'fields' => [
                    ['key' => 'pathAclEnabled', 'type' => 'bool', 'label' => 'Povoliť path ACL', 'default' => false, 'rules' => ['bool'], 'help' => 'Obmedzí prístup k vybraným cestám flat-file obsahu podľa rolí alebo oprávnení.'],
                    ['key' => 'pathAclRulesJson', 'type' => 'text', 'label' => 'Path ACL pravidlá (JSON)', 'default' => '[]', 'rules' => ['string', 'max:50000'], 'help' => 'Spravované cez vizuálny editor v administrácii.'],
                    ['key' => 'permissionsAdmin', 'type' => 'text', 'label' => 'Oprávnenia ADMIN', 'default' => 'user:manage,content:manage,media:manage,settings:manage,git:publish,gallery:manage,logs:view,metrics:read', 'rules' => ['required', 'string', 'max:5000']],
                    ['key' => 'permissionsEditor', 'type' => 'text', 'label' => 'Oprávnenia EDITOR', 'default' => 'content:create,content:edit,content:delete,media:upload,media:delete', 'rules' => ['required', 'string', 'max:5000']],
                    ['key' => 'permissionsUser', 'type' => 'text', 'label' => 'Oprávnenia USER', 'default' => 'content:view,profile:edit', 'rules' => ['required', 'string', 'max:5000']],
                ],
            ],
            'firewall' => [
                'label' => 'Firewall (WAF)',
                'fields' => [
                    ['key' => 'enabled', 'type' => 'bool', 'label' => 'Zapnúť firewall', 'default' => true, 'rules' => ['bool'], 'help' => 'Interný WAF skenuje URI, query, User-Agent a (voliteľne) POST/JSON telo pred spracovaním požiadavky.'],
                    ['key' => 'scanRequestBody', 'type' => 'bool', 'label' => 'Skenuj POST/JSON telo', 'default' => true, 'rules' => ['bool'], 'help' => 'Mutujúce requesty (okrem editorov obsahu a multipart uploadov). Editor API (/api/pages, /api/articles, drafts, code-editor) je vyňaté kvôli false positive.'],
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
                    ['key' => 'requestLogging', 'type' => 'bool', 'label' => 'Logovať HTTP requesty', 'default' => true, 'rules' => ['bool'], 'help' => 'API requesty → záznam s timestamp, IP, status, duration. Výnimky: /api/health, /api/debug/client-event, /api/admin/logs*. 404 = INFO (nie WARNING).'],
                    ['key' => 'minSeverity', 'type' => 'enum', 'label' => 'Min. úroveň zápisu', 'default' => 'debug', 'options' => ['debug', 'info', 'warning', 'error', 'critical'], 'rules' => ['required', 'in:debug,info,warning,error,critical'], 'help' => 'Nižšie úrovne sa neukladajú (HTTP access log).'],
                    ['key' => 'retentionDays', 'type' => 'int', 'label' => 'Retencia logov (dni)', 'default' => 30, 'rules' => ['required', 'int', 'min:1', 'max:365'], 'help' => 'Staršie denné súbory sa vymažú (purge v admin Logy).'],
                    ['key' => 'slowRequestMs', 'type' => 'int', 'label' => 'Pomalý request (ms)', 'default' => 2000, 'rules' => ['required', 'int', 'min:100', 'max:60000'], 'help' => 'Requesty nad tento limit sa logujú ako WARNING.'],
                    ['key' => 'logAuthEndpoints', 'type' => 'bool', 'label' => 'Logovať auth endpointy', 'default' => false, 'rules' => ['bool'], 'help' => 'Login/register cesty — bez tela, len metadata (IP, status).'],
                ],
            ],
            'feeds' => [
                'label' => 'RSS & Sitemap',
                'fields' => [
                    ['key' => 'enabled', 'type' => 'bool', 'label' => 'Povoliť feedy', 'default' => true, 'rules' => ['bool'], 'help' => 'Zapnuté = RSS a sitemap.xml sú dostupné. Vypnuté = feedy vracajú 404.'],
                    ['key' => 'title', 'type' => 'string', 'label' => 'Názov RSS kanála', 'default' => '', 'rules' => ['string', 'max:120'], 'help' => 'Prázdne = názov stránky z všeobecných nastavení.'],
                    ['key' => 'description', 'type' => 'text', 'label' => 'Popis RSS kanála', 'default' => '', 'rules' => ['string', 'max:500']],
                    ['key' => 'itemsLimit', 'type' => 'int', 'label' => 'Počet položiek v RSS', 'default' => 20, 'rules' => ['required', 'int', 'min:1', 'max:100']],
                    ['key' => 'includePages', 'type' => 'bool', 'label' => 'Sitemap: podstránky', 'default' => true, 'rules' => ['bool'], 'help' => 'Zapnuté = statické stránky sú v sitemap. Vypnuté = stránky v sitemap chýbajú.'],
                    ['key' => 'includeArticles', 'type' => 'bool', 'label' => 'RSS/Sitemap: články', 'default' => true, 'rules' => ['bool'], 'help' => 'Zapnuté = články v RSS a sitemap. Vypnuté = články v feedoch chýbajú.'],
                ],
            ],
            'seo' => [
                'label' => 'SEO',
                'fields' => [
                    ['key' => 'titleTemplate', 'type' => 'string', 'label' => 'Šablóna titulku', 'default' => '%title% | %siteName%', 'rules' => ['required', 'string', 'max:120'], 'help' => 'Placeholders: %title%, %siteName%'],
                    ['key' => 'defaultDescription', 'type' => 'text', 'label' => 'Predvolený meta popis', 'default' => '', 'rules' => ['string', 'max:300']],
                    ['key' => 'defaultImage', 'type' => 'url', 'label' => 'Predvolený OG obrázok (URL)', 'default' => '', 'rules' => ['url', 'max:512']],
                    ['key' => 'robotsDefault', 'type' => 'string', 'label' => 'Robots (predvolene)', 'default' => 'index,follow', 'rules' => ['required', 'string', 'max:64']],
                    ['key' => 'allowSearchIndexing', 'type' => 'bool', 'label' => 'Povoliť indexovanie vyhľadávačmi', 'default' => true, 'rules' => ['bool'], 'help' => 'Vypnuté = robots.txt obsahuje Disallow: / a meta tagy noindex (okrem stránok s vlastným noIndex).'],
                    ['key' => 'twitterCard', 'type' => 'enum', 'label' => 'Twitter card typ', 'default' => 'summary_large_image', 'options' => ['summary', 'summary_large_image'], 'rules' => ['required', 'in:summary,summary_large_image']],
                ],
            ],
            'media' => [
                'label' => 'Media / DAM',
                'fields' => [
                    ['key' => 'storageDriver', 'type' => 'enum', 'label' => 'Media storage driver', 'default' => 'local', 'options' => ['local', 's3'], 'rules' => ['required', 'in:local,s3'], 'help' => 'Iteration 72: local = flat-file binaries under media/. S3 appears in UI but falls back to local until the driver ships.'],
                    ['key' => 's3Endpoint', 'type' => 'url', 'label' => 'S3 endpoint URL', 'default' => '', 'rules' => ['url', 'max:512'], 'help' => 'Reserved for S3-compatible driver (not active in MVP). Must pass OutboundUrlGuard when enabled.'],
                    ['key' => 's3Region', 'type' => 'string', 'label' => 'S3 region', 'default' => '', 'rules' => ['string', 'max:64']],
                    ['key' => 's3Bucket', 'type' => 'string', 'label' => 'S3 bucket', 'default' => '', 'rules' => ['string', 'max:255']],
                    ['key' => 's3KeyId', 'type' => 'string', 'label' => 'S3 access key ID', 'default' => '', 'rules' => ['string', 'max:255']],
                    ['key' => 's3Secret', 'type' => 'password', 'label' => 'S3 secret access key', 'default' => '', 'rules' => ['string', 'max:255']],
                    ['key' => 's3PathStyle', 'type' => 'bool', 'label' => 'S3 path-style addressing', 'default' => false, 'rules' => ['bool']],
                    ['key' => 's3PublicBaseUrl', 'type' => 'url', 'label' => 'S3 public base URL', 'default' => '', 'rules' => ['url', 'max:512'], 'help' => 'CDN or public bucket URL. Rejects javascript: and invalid schemes when validated.'],
                    ['key' => 's3Visibility', 'type' => 'enum', 'label' => 'S3 default visibility', 'default' => 'private', 'options' => ['private', 'public'], 'rules' => ['required', 'in:private,public']],
                    ['key' => 'allowedMimeTypes', 'type' => 'text', 'label' => 'Povolené MIME typy', 'default' => 'image/jpeg,image/png,image/gif,image/webp,image/svg+xml,application/pdf', 'rules' => ['required', 'string', 'max:2000'], 'help' => 'Oddeľte čiarkou. Ovplyvňuje upload v Media Library.'],
                    ['key' => 'maxUploadSizeKb', 'type' => 'int', 'label' => 'Max. veľkosť uploadu (KB)', 'default' => 5120, 'rules' => ['required', 'int', 'min:64', 'max:51200'], 'help' => '5120 KB = 5 MB.'],
                    ['key' => 'stockImagesEnabled', 'type' => 'bool', 'label' => 'Povoliť stock knižnicu', 'default' => true, 'rules' => ['bool'], 'help' => 'Tlačidlo „Generovať z knižnice“ v Media Library.'],
                    ['key' => 'stockImageTopic', 'type' => 'enum', 'label' => 'Téma stock obrázkov', 'default' => 'tech', 'options' => ['tech', 'business', 'food', 'travel', 'health', 'nature', 'general'], 'rules' => ['required', 'in:tech,business,food,travel,health,nature,general'], 'help' => 'Obrázky sa vyberajú podľa zamerania webu (IT, varenie, cestovanie…).'],
                ],
            ],
            'sso' => [
                'label' => 'SSO / OAuth',
                'fields' => [
                    ['key' => 'enabled', 'type' => 'bool', 'label' => 'Povoliť SSO prihlásenie', 'default' => false, 'rules' => ['bool'], 'help' => 'Zapnuté = externé prihlásenie je dostupné (podľa zapnutých providerov). Vypnuté = len lokálne účty.'],
                    ['key' => 'defaultRole', 'type' => 'enum', 'label' => 'Predvolená rola (nový účet)', 'default' => 'EDITOR', 'options' => ['USER', 'EDITOR', 'ADMIN'], 'rules' => ['required', 'in:USER,EDITOR,ADMIN']],
                    ['key' => 'githubEnabled', 'type' => 'bool', 'label' => 'GitHub OAuth', 'default' => false, 'rules' => ['bool'], 'help' => 'Zapnuté = tlačidlo „Prihlásiť cez GitHub“. Vypnuté = GitHub login skrytý.'],
                    ['key' => 'githubClientId', 'type' => 'string', 'label' => 'GitHub Client ID', 'default' => '', 'rules' => ['string', 'max:255']],
                    ['key' => 'githubClientSecret', 'type' => 'password', 'label' => 'GitHub Client Secret', 'default' => '', 'rules' => ['string', 'max:255']],
                    ['key' => 'genericEnabled', 'type' => 'bool', 'label' => 'Generic OAuth2', 'default' => false, 'rules' => ['bool'], 'help' => 'Zapnuté = Generic OAuth2 provider podľa nižšie zadaných údajov. Vypnuté = provider vypnutý.'],
                    ['key' => 'genericName', 'type' => 'string', 'label' => 'Generic provider name', 'default' => 'OAuth', 'rules' => ['string', 'max:64']],
                    ['key' => 'genericClientId', 'type' => 'string', 'label' => 'Generic Client ID', 'default' => '', 'rules' => ['string', 'max:255']],
                    ['key' => 'genericClientSecret', 'type' => 'password', 'label' => 'Generic Client Secret', 'default' => '', 'rules' => ['string', 'max:255']],
                    ['key' => 'genericAuthorizeUrl', 'type' => 'url', 'label' => 'Authorize URL', 'default' => '', 'rules' => ['url', 'max:512']],
                    ['key' => 'genericTokenUrl', 'type' => 'url', 'label' => 'Token URL', 'default' => '', 'rules' => ['url', 'max:512']],
                    ['key' => 'genericUserInfoUrl', 'type' => 'url', 'label' => 'UserInfo URL', 'default' => '', 'rules' => ['url', 'max:512']],
                    ['key' => 'genericScope', 'type' => 'string', 'label' => 'OAuth scope', 'default' => 'openid email profile', 'rules' => ['string', 'max:255']],
                ],
            ],
            'cmsInfo' => [
                'label' => 'PaginiumCMS – info',
                'informational' => true,
                'fields' => [],
            ],
            'systemUpdate' => [
                'label' => 'System update (production deploy)',
                'superAdminOnly' => true,
                'fields' => [
                    ['key' => 'deployEnabled', 'type' => 'bool', 'label' => 'Enable admin deploy', 'default' => false, 'rules' => ['bool'], 'help' => 'SUPER_ADMIN can enqueue code deploy from Platform → System update. Ignored when DEMO_MODE=true.'],
                    ['key' => 'githubOwner', 'type' => 'string', 'label' => 'GitHub owner', 'default' => 'techberode', 'rules' => ['string', 'max:120']],
                    ['key' => 'githubRepo', 'type' => 'string', 'label' => 'GitHub repository', 'default' => 'paginiumcms-architecture', 'rules' => ['string', 'max:120']],
                    ['key' => 'githubToken', 'type' => 'password', 'label' => 'GitHub token (repo read)', 'default' => '', 'rules' => ['string', 'max:512'], 'help' => 'Fine-grained or classic token with read access to code and releases.'],
                    ['key' => 'defaultBranch', 'type' => 'string', 'label' => 'Default branch', 'default' => 'main', 'rules' => ['required', 'string', 'max:120']],
                    ['key' => 'allowDeployMain', 'type' => 'bool', 'label' => 'Allow deploy from branch (origin/…)', 'default' => false, 'rules' => ['bool']],
                    ['key' => 'allowDeployTags', 'type' => 'bool', 'label' => 'Allow deploy from semver tags', 'default' => true, 'rules' => ['bool']],
                    ['key' => 'webhookDeployEnabled', 'type' => 'bool', 'label' => 'Enable GitHub release webhook deploy', 'default' => false, 'rules' => ['bool'], 'help' => 'When enabled, POST /api/webhooks/github/release queues deploy on release published (HMAC secret required).'],
                    ['key' => 'githubWebhookSecret', 'type' => 'password', 'label' => 'GitHub webhook secret', 'default' => '', 'rules' => ['string', 'max:255'], 'help' => 'Same secret as configured in GitHub → Settings → Webhooks → Secret. Never logged.'],
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

    public static function isSuperAdminOnly(string $group): bool
    {
        return (self::groups()[$group]['superAdminOnly'] ?? false) === true;
    }

    public static function isInformational(string $group): bool
    {
        return (self::groups()[$group]['informational'] ?? false) === true;
    }

    public static function isEditorAccessible(string $group): bool
    {
        return $group === 'editor';
    }

    public static function isEditorOnlyUser(?User $user): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        $roles = $user->getRoles();

        return in_array('EDITOR', $roles, true)
            && !in_array('ADMIN', $roles, true)
            && !in_array('SUPER_ADMIN', $roles, true);
    }

    public static function userCanAccessGroup(?User $user, string $group): bool
    {
        if ($user === null) {
            return false;
        }

        if (self::isSuperAdminOnly($group) && !in_array('SUPER_ADMIN', $user->getRoles(), true)) {
            return false;
        }

        if (self::isEditorOnlyUser($user)) {
            return self::isEditorAccessible($group);
        }

        return in_array('ADMIN', $user->getRoles(), true)
            || in_array('SUPER_ADMIN', $user->getRoles(), true);
    }

    /**
     * @param array<string, SettingGroup> $schema
     * @return array<string, SettingGroup>
     */
    public static function filterSchemaForUser(array $schema, ?User $user): array
    {
        if ($user instanceof User && in_array('SUPER_ADMIN', $user->getRoles(), true)) {
            return $schema;
        }

        if (self::isEditorOnlyUser($user)) {
            return isset($schema['editor']) ? ['editor' => $schema['editor']] : [];
        }

        foreach ($schema as $group => $definition) {
            if (($definition['superAdminOnly'] ?? false) === true) {
                unset($schema[$group]);
            }
        }

        return $schema;
    }

    /**
     * @param array<string, array<int|string, mixed>> $values
     * @return array<string, array<int|string, mixed>>
     */
    public static function filterValuesForUser(array $values, ?User $user): array
    {
        if ($user instanceof User && in_array('SUPER_ADMIN', $user->getRoles(), true)) {
            return $values;
        }

        if (self::isEditorOnlyUser($user)) {
            return isset($values['editor']) ? ['editor' => $values['editor']] : [];
        }

        foreach (array_keys($values) as $group) {
            if (self::isSuperAdminOnly($group)) {
                unset($values[$group]);
            }
        }

        return $values;
    }

    /**
     * Mapa citlivých polí (typ `password`) na šifrovanie „at-rest".
     * Kľúč = skupina, hodnota = zoznam názvov polí, ktoré treba šifrovať.
     *
     * @return array<string, list<string>>
     */
    public static function secretKeys(): array
    {
        $secrets = [];
        foreach (self::groups() as $group => $definition) {
            foreach ($definition['fields'] as $field) {
                if ($field['type'] === 'password') {
                    $secrets[$group][] = (string) $field['key'];
                }
            }
        }

        return $secrets;
    }

    /**
     * Je pole v danej skupine citlivé (typ `password`)?
     */
    public static function isSecretField(string $group, string $key): bool
    {
        return in_array($key, self::secretKeys()[$group] ?? [], true);
    }
}
