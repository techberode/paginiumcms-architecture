<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Exception;

/**
 * Výnimka pre prípad, že súbor neexistuje.
 */
class FileNotFoundException extends FlatFileException
{
    private string $path;

    public function __construct(string $path, int $code = 0, ?\Throwable $previous = null)
    {
        $this->path = $path;
        parent::__construct(sprintf('Súbor neexistuje: %s', $path), $code, $previous);
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
