<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Comments\Services;

/**
 * Static disposable e-mail domain list (It.80c).
 */
final class DisposableEmailDomainList
{
    /** @var array<string, list<string>> */
    private static array $domainsByFile = [];

    public function __construct(
        private string $listFile = '',
    ) {
        if ($this->listFile === '') {
            $this->listFile = dirname(__DIR__, 4) . '/config/spam/disposable_email_domains.txt';
        }
    }

    public function isDisposable(string $email): bool
    {
        $email = strtolower(trim($email));
        if ($email === '' || !str_contains($email, '@')) {
            return false;
        }

        $domain = substr($email, strrpos($email, '@') + 1);
        if ($domain === '') {
            return false;
        }

        return in_array($domain, $this->domains(), true);
    }

    /**
     * @return list<string>
     */
    private function domains(): array
    {
        if (isset(self::$domainsByFile[$this->listFile])) {
            return self::$domainsByFile[$this->listFile];
        }

        if (!is_file($this->listFile)) {
            self::$domainsByFile[$this->listFile] = [];

            return self::$domainsByFile[$this->listFile];
        }

        $lines = file($this->listFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            self::$domainsByFile[$this->listFile] = [];

            return self::$domainsByFile[$this->listFile];
        }

        $domains = [];
        foreach ($lines as $line) {
            $line = strtolower(trim((string) $line));
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $domains[] = $line;
        }

        self::$domainsByFile[$this->listFile] = $domains;

        return self::$domainsByFile[$this->listFile];
    }

    /**
     * Resets cached domains (tests only).
     */
    public static function resetCacheForTesting(): void
    {
        self::$domainsByFile = [];
    }
}
