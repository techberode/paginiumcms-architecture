<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\Capabilities;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Heuristic: plugin PHP must not use runtime APIs that are missing from capabilities[] (It.89d).
 */
final class PluginCapabilityUsageScanner
{
    /** @var list<string> */
    private const NETWORK_FUNCTIONS = [
        'file_get_contents',
        'curl_init',
        'curl_exec',
        'fsockopen',
        'pfsockopen',
        'stream_socket_client',
        'ftp_connect',
    ];

    /** @var list<string> */
    private const FORBIDDEN_TYPES = [
        'flatfilestorage',
        'contentrepositoryinterface',
        'mediarepositoryinterface',
        'plugincapabilitybroker',
    ];

    /**
     * @param array<string, mixed> $manifest
     * @return array<string, list<string>>
     */
    public function scan(string $absoluteRoot, array $manifest): array
    {
        $capabilities = $this->capabilitiesFromManifest($manifest);
        $errors = [];

        foreach ($this->phpFiles($absoluteRoot) as $relative => $absolute) {
            $content = @file_get_contents($absolute);
            if ($content === false) {
                $errors[$relative][] = 'Unable to read file';
                continue;
            }

            foreach ($this->scanPhp($content, $capabilities) as $message) {
                $errors[$relative][] = $message;
            }
        }

        return $errors;
    }

    /**
     * @param list<string> $capabilities
     * @return list<string>
     */
    public function scanPhp(string $content, array $capabilities): array
    {
        $tokens = token_get_all($content);
        if ($tokens === []) {
            return [];
        }

        $hasContent = $this->hasAny($capabilities, [
            PluginCapabilityCatalog::CONTENT_READ,
            PluginCapabilityCatalog::CONTENT_WRITE,
            PluginCapabilityCatalog::CONTENT_WRITE_OWN,
        ]);
        $hasMedia = $this->hasAny($capabilities, [
            PluginCapabilityCatalog::MEDIA_READ,
            PluginCapabilityCatalog::MEDIA_WRITE,
        ]);
        $hasOutbound = $this->hasOutbound($capabilities);

        $violations = [];
        foreach ($tokens as $index => $token) {
            if (!is_array($token)) {
                continue;
            }

            $type = $token[0];
            $text = strtolower($token[1]);

            if (in_array($type, [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                foreach (self::FORBIDDEN_TYPES as $needle) {
                    if (str_contains(str_replace('\\', '', $text), $needle)) {
                        $violations[] = 'Forbidden platform type: ' . $token[1];
                    }
                }
            }

            if ($type === T_STRING && in_array($text, self::NETWORK_FUNCTIONS, true)) {
                $next = $tokens[$this->nextCodeIndex($tokens, $index)] ?? null;
                if ($this->isOpenParen($next) && !$hasOutbound) {
                    $violations[] = 'Undeclared capability usage: ' . $token[1] . '() requires network:outbound:*';
                }
            }

            if ($type !== T_STRING) {
                continue;
            }

            $prev = $this->previousCodeToken($tokens, $index);
            if (!$this->isObjectOperator($prev)) {
                continue;
            }

            $next = $tokens[$this->nextCodeIndex($tokens, $index)] ?? null;
            if (!$this->isOpenParen($next)) {
                continue;
            }

            if ($text === 'content' && !$hasContent) {
                $violations[] = 'Undeclared capability usage: content() requires content:read or content:write';
            }
            if ($text === 'media' && !$hasMedia) {
                $violations[] = 'Undeclared capability usage: media() requires media:read';
            }
        }

        return array_values(array_unique($violations));
    }

    /**
     * @param array<string, mixed> $manifest
     * @return list<string>
     */
    private function capabilitiesFromManifest(array $manifest): array
    {
        $raw = $manifest['capabilities'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = trim($item);
            }
        }

        return $out;
    }

    /**
     * @param list<string> $capabilities
     * @param list<string> $needed
     */
    private function hasAny(array $capabilities, array $needed): bool
    {
        foreach ($needed as $capability) {
            if (in_array($capability, $capabilities, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $capabilities
     */
    private function hasOutbound(array $capabilities): bool
    {
        foreach ($capabilities as $capability) {
            if (str_starts_with($capability, PluginCapabilityCatalog::OUTBOUND_PREFIX)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    private function phpFiles(string $root): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $root = rtrim($root, '/');
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $absolute = $file->getPathname();
            $relative = ltrim(str_replace('\\', '/', substr($absolute, strlen($root))), '/');
            if ($relative !== '') {
                $files[$relative] = $absolute;
            }
        }

        ksort($files);

        return $files;
    }

    /**
     * @param array<int, string|array{0: int, 1: string, 2?: int}> $tokens
     */
    private function nextCodeIndex(array $tokens, int $fromExclusive): int
    {
        $i = $fromExclusive + 1;
        $count = count($tokens);
        while ($i < $count) {
            $token = $tokens[$i];
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $i++;
                continue;
            }

            return $i;
        }

        return $count;
    }

    /**
     * @param array<int, string|array{0: int, 1: string, 2?: int}> $tokens
     */
    private function previousCodeToken(array $tokens, int $index): mixed
    {
        $i = $index - 1;
        while ($i >= 0) {
            $token = $tokens[$i];
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $i--;
                continue;
            }

            return $token;
        }

        return null;
    }

    private function isObjectOperator(mixed $token): bool
    {
        if (!is_array($token)) {
            return false;
        }

        if ($token[0] === T_OBJECT_OPERATOR) {
            return true;
        }

        return defined('T_NULLSAFE_OBJECT_OPERATOR') && $token[0] === T_NULLSAFE_OBJECT_OPERATOR;
    }

    private function isOpenParen(mixed $token): bool
    {
        return $token === '(' || (is_array($token) && trim((string) $token[1]) === '(');
    }
}
