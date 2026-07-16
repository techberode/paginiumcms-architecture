<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Models\Content;

/**
 * === Služba: ContentRevision ===
 * Počíta deterministický „revízny odtlačok" obsahu pre optimistické zamykanie
 * a detekciu konfliktov (Iterácia 2).
 *
 * Princíp:
 *  - revízia = sha1( content + oddeľovač + kanonický JSON front matter )
 *  - je nezávislá od času a poradia kľúčov (ksort), takže rovnaký obsah = rovnaká revízia
 *  - klient dostane revíziu pri načítaní (GET) a pošle ju späť pri uložení (PUT) ako `baseRevision`.
 *    Ak sa medzitým súbor na disku zmenil, revízie sa nezhodujú → konflikt.
 *
 * Trieda je bezstavová (žiadne závislosti), takže sa jednoducho znovupoužíva
 * v ktoromkoľvek module Jadra (obsah, koncepty, assety…).
 */
final class ContentRevision
{
    private const SEPARATOR = "\n----8<----frontmatter----8<----\n";

    /**
     * Vypočíta revíziu z modelu obsahu.
     */
    public function forContent(Content $content): string
    {
        return $this->compute($content->getContent(), $content->getFrontMatter());
    }

    /**
     * Vypočíta revíziu z raw hodnôt.
     *
     * @param array<int|string, mixed> $frontMatter
 */public function compute(string $content, array $frontMatter): string
    {
        return sha1($content . self::SEPARATOR . $this->canonicalize($frontMatter));
    }

    /**
     * Overí, či `baseRevision` zodpovedá aktuálnej revízii obsahu.
     * Prázdny `baseRevision` znamená „klient revíziu neposlal" → bez kontroly (spätná kompatibilita).
     */
    public function matches(Content $content, ?string $baseRevision): bool
    {
        if ($baseRevision === null || $baseRevision === '') {
            return true;
        }

        return hash_equals($this->forContent($content), $baseRevision);
    }

    /**
     * Kanonická (stabilná) serializácia front matter – rekurzívne zoradené kľúče.
     *
     * @param array<int|string, mixed> $data
 */private function canonicalize(array $data): string
    {
        $this->ksortRecursive($data);

        return (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param array<int|string, mixed> $data
     */
    private function ksortRecursive(array &$data): void
    {
        ksort($data);
        foreach ($data as &$value) {
            if (is_array($value)) {
                $this->ksortRecursive($value);
            }
        }
        unset($value);
    }
}
