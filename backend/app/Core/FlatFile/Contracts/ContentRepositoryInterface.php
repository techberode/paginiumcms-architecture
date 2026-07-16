<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Contracts;

use PaginiumCMS\Core\FlatFile\Models\Content;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Http\Support\PaginationQuery;

/**
 * Rozhranie pre repozitár obsahu.
 *
 * Poskytuje CRUD operácie pre stránky, články a ďalší obsah.
 */
interface ContentRepositoryInterface
{
    /**
     * Nájde obsah podľa cesty.
     *
     * @param string $relativePath Relatívna cesta k súboru.
     * @return Content|null Inštancia obsahu alebo null.
     */
    public function findByPath(string $relativePath): ?Content;

    /**
     * Nájde obsah podľa slugu.
     *
     * @param string $slug Slug (napr. 'o-nas').
     * @param string $type Typ obsahu ('page' alebo 'article').
     * @return Content|null Inštancia obsahu alebo null.
     */
    public function findBySlug(string $slug, string $type = 'page'): ?Content;

    /**
     * Získa všetky stránky.
     *
     * @param array<int|string, mixed> $filters Voliteľné filtre (status, autor, atď.).
     * @return array<int, Page> Zoznam stránok.
 */public function findAllPages(array $filters = []): array;

    /**
     * Získa všetky články.
     *
     * @param array<int|string, mixed> $filters Voliteľné filtre (status, tagy, autor, atď.).
     * @return array<int, Article> Zoznam článkov.
 */public function findAllArticles(array $filters = []): array;

    /**
     * Uloží obsah.
     *
     * @param Content $content Inštancia obsahu.
     * @throws FlatFileException Ak uloženie zlyhá.
     */
    public function save(Content $content): void;

    /**
     * Vymaže obsah.
     *
     * @param Content $content Inštancia obsahu.
     * @param bool $permanent Trvalé vymazanie (preskočí kôš).
     * @throws FlatFileException Ak vymazanie zlyhá.
     */
    public function delete(Content $content, bool $permanent = false): void;

    /**
     * Získa počet položiek podľa typu.
     *
     * @param string $type Typ obsahu ('page' alebo 'article').
     * @param array<int|string, mixed> $filters Voliteľné filtre.
     * @return int Počet položiek.
 */public function count(string $type, array $filters = []): int;

    /**
     * Stránkovaný zoznam stránok cez content index (Iterácia 19).
     *
     * @return array{items: array<int, Page>, total: int}
     */
    public function findPagesPaginated(PaginationQuery $query): array;

    /**
     * Stránkovaný zoznam článkov cez content index (Iterácia 19).
     *
     * @return array{items: array<int, Article>, total: int}
     */
    public function findArticlesPaginated(PaginationQuery $query): array;
}
