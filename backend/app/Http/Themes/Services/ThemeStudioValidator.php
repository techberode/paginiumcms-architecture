<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Themes\Services;

use PaginiumCMS\Core\CodePolicy\Contracts\CodePolicyEngineInterface;
use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use PaginiumCMS\Core\CodePolicy\Services\UntrustedMarkupScanner;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Http\Themes\Exceptions\ThemeStudioException;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Support\LogSanitizer;
use RuntimeException;

/**
 * In-memory Theme Studio policy gate (It.88b). Never writes disk.
 *
 * @phpstan-type StudioMarker array{line: int, message: string}
 */
final class ThemeStudioValidator
{
    public function __construct(
        private ThemeStudioService $studio,
        private CodePolicyEngineInterface $codePolicy,
        private ThemeManifestValidator $manifests,
        private UntrustedMarkupScanner $markup,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @return array{valid: bool, relativePath: string, markers: list<StudioMarker>}
     */
    public function validate(string $themeId, string $relativePath, string $content): array
    {
        $id = trim($themeId);
        if ($id === '') {
            $id = 'untitled-theme';
        }
        if (!ThemeStudioService::isValidThemeId($id)) {
            throw new ThemeStudioException('Invalid theme id.', 400);
        }

        $path = $this->studio->assertBufferPath($relativePath);
        if (strlen($content) > ThemeStudioService::MAX_FILE_BYTES) {
            throw new ThemeStudioException('Theme file is too large to open in the studio.', 413);
        }

        $markers = $this->markup->scan($path, $content);
        $logicalPath = 'themes/' . $id . '/' . $path;

        try {
            $this->codePolicy->validateUntrusted($logicalPath, $content);
        } catch (CodePolicyViolationException $exception) {
            $markers = $this->mergeEngineMarkers($content, $markers, $exception->getErrors());
        }

        if (strtolower(basename($path)) === 'theme.json') {
            $markers = array_merge($markers, $this->manifestMarkers($id, $content));
        }

        usort($markers, static fn (array $a, array $b): int => $a['line'] <=> $b['line']);

        $valid = $markers === [];
        if (!$valid) {
            $this->logger?->warning('Theme studio validation failed', LogSanitizer::context([
                'themeId' => $id,
                'path' => $path,
                'markers' => (string) count($markers),
            ]));
        }

        return [
            'valid' => $valid,
            'relativePath' => $path,
            'markers' => $markers,
        ];
    }

    /**
     * @param list<StudioMarker> $existing
     * @param array<string, list<string>> $grouped
     * @return list<StudioMarker>
     */
    private function mergeEngineMarkers(string $content, array $existing, array $grouped): array
    {
        $known = [];
        foreach ($existing as $marker) {
            $known[$marker['message'] . ':' . $marker['line']] = true;
        }

        $hasMarkup = $existing !== [];
        foreach ($grouped as $group => $messages) {
            if ($hasMarkup && $group === 'security') {
                continue;
            }
            foreach ($messages as $message) {
                $line = $this->guessLine($content, $message);
                $key = $message . ':' . $line;
                if (isset($known[$key])) {
                    continue;
                }
                $known[$key] = true;
                $existing[] = [
                    'line' => $line,
                    'message' => $message,
                ];
            }
        }

        return $existing;
    }

    /**
     * @return list<StudioMarker>
     */
    private function manifestMarkers(string $themeId, string $content): array
    {
        try {
            /** @var array<string, mixed> $decoded */
            $decoded = JsonHelper::decode($content);
        } catch (\JsonException $exception) {
            return [[
                'line' => 1,
                'message' => 'theme.json is not valid JSON: ' . $exception->getMessage(),
            ]];
        }

        try {
            $this->manifests->validate($decoded, $themeId);
        } catch (RuntimeException $exception) {
            return [['line' => 1, 'message' => $exception->getMessage()]];
        }

        return [];
    }

    private function guessLine(string $content, string $message): int
    {
        $needles = [];
        if (str_contains($message, 'eval(')) {
            $needles[] = 'eval(';
        }
        if (str_contains($message, 'Function(')) {
            $needles[] = 'Function(';
        }
        if (str_contains($message, 'innerHTML')) {
            $needles[] = 'innerHTML';
        }
        if (str_contains($message, 'document.cookie')) {
            $needles[] = 'document.cookie';
        }
        if (str_contains($message, 'document.write')) {
            $needles[] = 'document.write';
        }

        foreach ($needles as $needle) {
            $offset = stripos($content, $needle);
            if ($offset !== false) {
                return substr_count(substr($content, 0, $offset), "\n") + 1;
            }
        }

        return 1;
    }
}
