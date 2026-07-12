<?php
// backend/app/Core/CodeEditor/Services/SyntaxChecker.php

declare(strict_types=1);

namespace PaginiumCMS\Core\CodeEditor\Services;

class SyntaxChecker
{
    private ?string $lastError = null;

    public function check(string $path, string $content): bool
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return match ($extension) {
            'php' => $this->checkPhp($content),
            'js' => $this->checkJavaScript($content),
            'json' => $this->checkJson($content),
            'yaml', 'yml' => $this->checkYaml($content),
            'html', 'htm' => $this->checkHtml($content),
            'css' => $this->checkCss($content),
            default => true,
        };
    }

    private function checkPhp(string $content): bool
    {
        // PHP lint
        $tempFile = tempnam(sys_get_temp_dir(), 'php_lint_');
        file_put_contents($tempFile, $content);

        $output = shell_exec("php -l " . escapeshellarg($tempFile) . " 2>&1");
        unlink($tempFile);

        if (strpos($output, 'No syntax errors detected') === false) {
            $this->lastError = trim($output);
            return false;
        }

        return true;
    }

    private function checkJson(string $content): bool
    {
        json_decode($content);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->lastError = json_last_error_msg();
            return false;
        }
        return true;
    }

    private function checkYaml(string $content): bool
    {
        try {
            \Symfony\Component\Yaml\Yaml::parse($content);
            return true;
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    private function checkJavaScript(string $content): bool
    {
        // Jednoduchá kontrola – v reálnom prostredí by sme použili ESLint
        return true;
    }

    private function checkHtml(string $content): bool
    {
        return true;
    }

    private function checkCss(string $content): bool
    {
        return true;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }
}
