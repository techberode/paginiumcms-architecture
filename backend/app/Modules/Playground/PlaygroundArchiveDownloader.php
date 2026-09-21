<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Playground;

use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Modules\Playground\Contracts\PlaygroundArchiveDownloaderInterface;
use RuntimeException;

/**
 * HTTPS zip download with OutboundUrlGuard. No redirect following (SSRF).
 */
final class PlaygroundArchiveDownloader implements PlaygroundArchiveDownloaderInterface
{
    private const MAX_BYTES = 5_000_000;
    private const TIMEOUT_SECONDS = 30;

    public function __construct(
        private OutboundUrlGuard $guard,
    ) {
    }

    public function download(string $url, string $token): string
    {
        $this->guard->assertAllowed($url);

        $handle = curl_init($url);
        if ($handle === false) {
            throw new RuntimeException('Unable to start pack download.');
        }

        $headers = [
            'Accept: application/zip, application/octet-stream',
            'User-Agent: PaginiumCMS-PlaygroundImport',
        ];
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $body = '';
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_WRITEFUNCTION => static function ($ch, string $chunk) use (&$body): int {
                unset($ch);
                $body .= $chunk;
                if (strlen($body) > self::MAX_BYTES) {
                    return 0;
                }

                return strlen($chunk);
            },
        ]);

        $ok = curl_exec($handle);
        $code = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if ($ok === false || $body === '') {
            throw new RuntimeException('Pack download failed.');
        }
        if (strlen($body) > self::MAX_BYTES) {
            throw new RuntimeException('Pack archive is too large.');
        }
        if ($code >= 400) {
            throw new RuntimeException('Pack download HTTP ' . $code);
        }

        return $body;
    }
}
