<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Notification\Services;

use PaginiumCMS\Core\Notification\Adapters\DiscordAdapter;
use PaginiumCMS\Core\Notification\Adapters\EmailAdapter;
use PaginiumCMS\Core\Notification\Adapters\NtfyAdapter;
use PaginiumCMS\Core\Notification\Adapters\TelegramAdapter;
use PaginiumCMS\Core\Notification\Adapters\WebhookAdapter;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Builds a NotificationService from flat-file settings (Iteration 6, auth It.47).
 */
final class NotificationFactory
{
    public static function create(SettingsRepositoryInterface $settings): NotificationService
    {
        $service = new NotificationService();
        $all = $settings->all();
        $smtp = is_array($all['smtp'] ?? null) ? $all['smtp'] : [];
        $connectors = is_array($all['connectors'] ?? null) ? $all['connectors'] : [];

        $fromEmail = (string) ($smtp['fromEmail'] ?? '');
        $fromName = (string) ($smtp['fromName'] ?? 'PaginiumCMS');
        if ($fromEmail === '' && isset($all['general']['adminEmail'])) {
            $fromEmail = (string) $all['general']['adminEmail'];
        }

        $emailEnabled = (bool) ($connectors['emailEnabled'] ?? false) || (bool) ($smtp['enabled'] ?? false);
        if ($emailEnabled && $fromEmail !== '') {
            $transport = null;
            if ((bool) ($smtp['enabled'] ?? false) && (string) ($smtp['host'] ?? '') !== '') {
                $transport = new SmtpTransport(
                    (string) $smtp['host'],
                    (int) ($smtp['port'] ?? 587),
                    (string) ($smtp['encryption'] ?? 'tls'),
                    (string) ($smtp['username'] ?? ''),
                    (string) ($smtp['password'] ?? '')
                );
            }
            $service->addAdapter('email', new EmailAdapter($fromEmail, $fromName, $transport));
        }

        if ((bool) ($connectors['ntfyEnabled'] ?? false)
            && (string) ($connectors['ntfyTopic'] ?? '') !== ''
            && self::isNtfyAuthReady($connectors)) {
            $service->addAdapter('ntfy', new NtfyAdapter(
                (string) ($connectors['ntfyServer'] ?? 'https://ntfy.sh'),
                (string) $connectors['ntfyTopic'],
                (string) ($connectors['ntfyAuthMode'] ?? 'none'),
                (string) ($connectors['ntfyAccessToken'] ?? ''),
                (string) ($connectors['ntfyUsername'] ?? ''),
                (string) ($connectors['ntfyPassword'] ?? '')
            ));
        }

        if ((bool) ($connectors['discordEnabled'] ?? false) && (string) ($connectors['discordWebhookUrl'] ?? '') !== '') {
            $service->addAdapter('discord', new DiscordAdapter((string) $connectors['discordWebhookUrl']));
        }

        if ((bool) ($connectors['telegramEnabled'] ?? false)
            && (string) ($connectors['telegramBotToken'] ?? '') !== ''
            && (string) ($connectors['telegramChatId'] ?? '') !== '') {
            $service->addAdapter('telegram', new TelegramAdapter(
                (string) $connectors['telegramBotToken'],
                (string) $connectors['telegramChatId']
            ));
        }

        if ((bool) ($connectors['webhookEnabled'] ?? false) && (string) ($connectors['webhookUrl'] ?? '') !== '') {
            $service->addAdapter('webhook', new WebhookAdapter(
                (string) $connectors['webhookUrl'],
                (string) ($connectors['webhookSecret'] ?? ''),
                (string) ($connectors['webhookAuthHeader'] ?? 'X-Webhook-Secret')
            ));
        }

        return $service;
    }

    /**
     * @return list<array{name: string, label: string, enabled: bool, configured: bool, authenticated: bool, auth_mode: string|null}>
     */
    public static function connectorOverview(SettingsRepositoryInterface $settings): array
    {
        $service = self::create($settings);
        $all = $settings->all();
        $smtp = is_array($all['smtp'] ?? null) ? $all['smtp'] : [];
        $connectors = is_array($all['connectors'] ?? null) ? $all['connectors'] : [];
        $active = $service->getAdapters();

        $labels = [
            'email' => 'Email (SMTP)',
            'ntfy' => 'ntfy',
            'discord' => 'Discord',
            'telegram' => 'Telegram',
            'webhook' => 'Webhook',
        ];

        $overview = [];
        foreach ($labels as $name => $label) {
            $configured = self::isConnectorConfigured($name, $smtp, $connectors);
            $authenticated = self::isConnectorAuthenticated($name, $smtp, $connectors);
            $overview[] = [
                'name' => $name,
                'label' => $label,
                'enabled' => in_array($name, $active, true),
                'configured' => $configured,
                'authenticated' => $authenticated,
                'auth_mode' => $name === 'ntfy' ? (string) ($connectors['ntfyAuthMode'] ?? 'none') : null,
            ];
        }

        return $overview;
    }

    /**
     * @param array<string, mixed> $connectors
     * @param array<string, mixed> $smtp
     */
    public static function connectorAuthError(string $connector, array $connectors, array $smtp = []): ?string
    {
        if (!self::isConnectorConfigured($connector, $smtp, $connectors)) {
            return match ($connector) {
                'email' => 'Enable email/SMTP and set a from address.',
                'ntfy' => 'Enable ntfy and set a topic.',
                'discord' => 'Enable Discord and set a webhook URL.',
                'telegram' => 'Enable Telegram and set bot token + chat ID.',
                'webhook' => 'Enable webhook and set a URL.',
                default => 'Connector is not configured.',
            };
        }

        if (self::isConnectorAuthenticated($connector, $smtp, $connectors)) {
            return null;
        }

        return match ($connector) {
            'ntfy' => match ((string) ($connectors['ntfyAuthMode'] ?? 'none')) {
                'token' => 'Set ntfy access token (Settings → Connectors).',
                'basic' => 'Set ntfy username and password for Basic auth.',
                default => 'ntfy authentication is not satisfied.',
            },
            default => 'Connector credentials are incomplete.',
        };
    }

    /**
     * @param array<string, mixed> $smtp
     * @param array<string, mixed> $connectors
     */
    private static function isConnectorConfigured(string $name, array $smtp, array $connectors): bool
    {
        return match ($name) {
            'email' => ((bool) ($connectors['emailEnabled'] ?? false) || (bool) ($smtp['enabled'] ?? false))
                && (string) ($smtp['fromEmail'] ?? '') !== '',
            'ntfy' => (bool) ($connectors['ntfyEnabled'] ?? false)
                && (string) ($connectors['ntfyTopic'] ?? '') !== '',
            'discord' => (bool) ($connectors['discordEnabled'] ?? false)
                && (string) ($connectors['discordWebhookUrl'] ?? '') !== '',
            'telegram' => (bool) ($connectors['telegramEnabled'] ?? false)
                && (string) ($connectors['telegramBotToken'] ?? '') !== ''
                && (string) ($connectors['telegramChatId'] ?? '') !== '',
            'webhook' => (bool) ($connectors['webhookEnabled'] ?? false)
                && (string) ($connectors['webhookUrl'] ?? '') !== '',
            default => false,
        };
    }

    /**
     * @param array<string, mixed> $smtp
     * @param array<string, mixed> $connectors
     */
    private static function isConnectorAuthenticated(string $name, array $smtp, array $connectors): bool
    {
        if (!self::isConnectorConfigured($name, $smtp, $connectors)) {
            return false;
        }

        return match ($name) {
            'email', 'discord', 'telegram', 'webhook' => true,
            'ntfy' => self::isNtfyAuthReady($connectors),
            default => false,
        };
    }

    /**
     * @param array<string, mixed> $connectors
     */
    private static function isNtfyAuthReady(array $connectors): bool
    {
        return match ((string) ($connectors['ntfyAuthMode'] ?? 'none')) {
            'token' => (string) ($connectors['ntfyAccessToken'] ?? '') !== '',
            'basic' => (string) ($connectors['ntfyUsername'] ?? '') !== ''
                && (string) ($connectors['ntfyPassword'] ?? '') !== '',
            default => true,
        };
    }
}
