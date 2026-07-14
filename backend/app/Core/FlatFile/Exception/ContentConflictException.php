<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Exception;

/**
 * === Výnimka: ContentConflictException ===
 * Vyhodená pri optimistickom zamykaní, keď sa obsah na disku zmenil od chvíle,
 * kedy si ho používateľ načítal (`baseRevision` != aktuálna revízia).
 *
 * Nesie kontext potrebný pre 3-way merge / ConflictResolver (Iterácia 3):
 *  - serverContent / serverFrontMatter : aktuálny stav na disku
 *  - serverRevision                    : aktuálna revízia na disku
 *
 * Mapuje sa na HTTP 409 Conflict.
 */
final class ContentConflictException extends FlatFileException
{
    /**
     * @param array<string, mixed> $serverFrontMatter
     */
    public function __construct(
        private string $serverContent,
        private array $serverFrontMatter,
        private string $serverRevision,
        string $message = 'Obsah bol medzičasom zmenený iným používateľom.'
    ) {
        parent::__construct($message, 409);
    }

    public function getServerContent(): string
    {
        return $this->serverContent;
    }

    /**
     * @return array<string, mixed>
     */
    public function getServerFrontMatter(): array
    {
        return $this->serverFrontMatter;
    }

    public function getServerRevision(): string
    {
        return $this->serverRevision;
    }

    /**
     * Serializovateľný kontext konfliktu pre API odpoveď.
     *
     * @return array<string, mixed>
     */
    public function toContext(): array
    {
        return [
            'serverContent' => $this->serverContent,
            'serverFrontMatter' => $this->serverFrontMatter,
            'serverRevision' => $this->serverRevision,
        ];
    }
}
