<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Exception;

/**
 * Výnimka pre neplatný YAML Front Matter.
 */
class InvalidFrontMatterException extends FlatFileException
{
    private string $content;

    public function __construct(string $content, string $reason, int $code = 0, ?\Throwable $previous = null)
    {
        $this->content = $content;
        parent::__construct(sprintf('Neplatný Front Matter: %s', $reason), $code, $previous);
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
