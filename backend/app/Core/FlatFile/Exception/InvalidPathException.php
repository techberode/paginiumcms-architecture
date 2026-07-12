<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Exception;

/**
 * Výnimka pre neplatnú cestu (path traversal, zakázané znaky).
 */
class InvalidPathException extends FlatFileException
{
    private string $path;

    public function __construct(string $path, string $reason, int $code = 0, ?\Throwable $previous = null)
    {
        $this->path = $path;
        parent::__construct(sprintf('Neplatná cesta: %s (%s)', $path, $reason), $code, $previous);
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
