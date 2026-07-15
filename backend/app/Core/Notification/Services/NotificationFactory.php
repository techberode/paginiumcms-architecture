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
 * Builds a NotificationService from flat-file settings (Iteration 6).
 */
final class NotificationFactory
{
    public static function create(SettingsRepositoryInterface $settings): NotificationService
    {
        $service = new NotificationService();
        $all = $settings->all();
        $smtp = $all['smtp'] ?? [];
        $connectors = $all['connectors'] ?? [];

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

        if ((bool) ($connectors['ntfyEnabled'] ?? false) && (string) ($connectors['ntfyTopic'] ?? '') !== '') {
            $service->addAdapter('ntfy', new NtfyAdapter(
                (string) ($connectors['ntfyServer'] ?? 'https://ntfy.sh'),
                (string) $connectors['ntfyTopic']
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
                (string) ($connectors['webhookSecret'] ?? '')
            ));
        }

        return $service;
    }

    /**
     * @return list<array{name: string, label: string, enabled: bool}>
     */
    public static function connectorOverview(SettingsRepositoryInterface $settings): array
    {
        $service = self::create($settings);
        $labels = [
            'email' => 'Email (SMTP)',
            'ntfy' => 'ntfy',
            'discord' => 'Discord',
            'telegram' => 'Telegram',
            'webhook' => 'Webhook',
        ];
        $active = $service->getAdapters();
        $overview = [];
        foreach ($labels as $name => $label) {
            $overview[] = ['name' => $name, 'label' => $label, 'enabled' => in_array($name, $active, true)];
        }

        return $overview;
    }
}
