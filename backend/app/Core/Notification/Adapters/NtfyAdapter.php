<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Notification\Adapters;

use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;

class NtfyAdapter implements AdapterInterface
{
    public function __construct(
        private string $server,
        private string $topic,
        private string $authMode = 'none',
        private string $accessToken = '',
        private string $username = '',
        private string $password = ''
    ) {
    }

    /**
     * @return list<string>
     */
    public function buildAuthHeaders(): array
    {
        return match ($this->authMode) {
            'token' => $this->accessToken !== ''
                ? ['Authorization: Bearer ' . $this->accessToken]
                : [],
            'basic' => $this->username !== '' && $this->password !== ''
                ? ['Authorization: Basic ' . base64_encode($this->username . ':' . $this->password)]
                : [],
            default => [],
        };
    }

    /**
     * @param array<int|string, mixed> $options
     */
    public function send(string $to, string $subject, string $message, array $options = []): bool
    {
        $url = rtrim($this->server, '/') . '/' . rawurlencode($this->topic);

        // SSRF guard (C14): ntfy server je admin-konfigurovateľný.
        if (!OutboundUrlGuard::fromEnv()->isAllowed($url)) {
            return false;
        }

        $priority = $options['priority'] ?? 'default';
        $tags = $options['tags'] ?? 'paginiumcms';

        $html = isset($options['html']) ? (string) $options['html'] : '';
        $useHtml = $html !== '' && str_contains($html, '<');
        $body = $useHtml ? $html : $message;
        $contentType = $useHtml ? 'text/html; charset=utf-8' : 'text/plain; charset=utf-8';

        $headers = array_merge($this->buildAuthHeaders(), [
            'Title: ' . $subject,
            'Priority: ' . $priority,
            'Tags: ' . $tags,
            'Content-Type: ' . $contentType,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $body,
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);

        return @file_get_contents($url, false, $context) !== false;
    }
}
