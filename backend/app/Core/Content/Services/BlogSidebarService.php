<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content\Services;

use PaginiumCMS\Core\Analytics\Contracts\ReporterInterface;
use PaginiumCMS\Core\Content\Services\CategoryCatalogSeeder;
use PaginiumCMS\Core\Content\Services\CategoryRepository;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Support\PaginationQuery;

/**
 * Public blog sidebar widgets and popular-article ordering (It.84b).
 */
final class BlogSidebarService
{
    private const POPULAR_PERIOD = '30d';

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private ContentRepositoryInterface $repository,
        private ReporterInterface $reporter,
        private CategoryRepository $categories,
        private CategoryCatalogSeeder $categorySeeder,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPublicPayload(): array
    {
        $config = $this->sidebarConfig();

        if (!$config['enabled']) {
            return [
                'enabled' => false,
                'placement' => $config['placement'],
                'tags' => [],
                'categories' => [],
                'latest' => [],
                'popular' => [],
            ];
        }

        $facetFilters = ['status' => 'published'];
        $this->categorySeeder->seedMissingBundled();

        return [
            'enabled' => true,
            'placement' => $config['placement'],
            'tags' => $config['showTags']
                ? $this->repository->listDistinctTags('article', $facetFilters)
                : [],
            'categories' => $config['showCategories']
                ? $this->categories->summarizeForSlugs(
                    $this->repository->listDistinctCategories('article', $facetFilters)
                )
                : [],
            'latest' => $config['showLatest']
                ? $this->summarizeArticles($this->loadLatestArticles($config['latestCount']))
                : [],
            'popular' => $config['showPopular']
                ? $this->summarizePopular($config['popularCount'])
                : [],
        ];
    }

    public function isEnabled(): bool
    {
        return $this->sidebarConfig()['enabled'];
    }

    /**
     * Paginated article list sorted by analytics views (30d), then recency.
     *
     * @return array{items: array<int, Article>, total: int}
     */
    public function findArticlesByPopularity(PaginationQuery $query): array
    {
        $filters = $query->filters;
        if (!isset($filters['status'])) {
            $filters['status'] = 'published';
        }

        $articles = $this->repository->findAllArticles($filters);
        $articles = array_values(array_filter(
            $articles,
            static fn (Article $item): bool => $item->getStatus() === 'published'
        ));

        $scores = $this->popularViewScores();
        usort($articles, static function (Article $a, Article $b) use ($scores): int {
            $scoreA = $scores[$a->getSlug()] ?? 0;
            $scoreB = $scores[$b->getSlug()] ?? 0;
            if ($scoreA !== $scoreB) {
                return $scoreB <=> $scoreA;
            }

            return strcmp($b->getFrontMatter()['createdAt'] ?? '', $a->getFrontMatter()['createdAt'] ?? '');
        });

        if ($query->search !== '' && mb_strlen($query->search) >= PaginationQuery::MIN_SEARCH_LENGTH) {
            $needle = mb_strtolower($query->search);
            $articles = array_values(array_filter(
                $articles,
                static fn (Article $article): bool => str_contains(mb_strtolower($article->getTitle()), $needle)
                    || str_contains(mb_strtolower($article->getSlug()), $needle)
            ));
        }

        $total = count($articles);
        $offset = ($query->page - 1) * $query->perPage;

        return [
            'items' => array_slice($articles, $offset, $query->perPage),
            'total' => $total,
        ];
    }

    /**
     * @return array<string, int> slug => views
     */
    public function popularViewScores(): array
    {
        $scores = [];
        foreach ($this->reporter->getTopArticles(500, self::POPULAR_PERIOD) as $row) {
            $slug = $this->extractArticleSlugFromUri($row['uri']);
            if ($slug === '') {
                continue;
            }
            $scores[$slug] = ($scores[$slug] ?? 0) + $row['views'];
        }

        return $scores;
    }

    /**
     * @return list<Article>
     */
    private function loadLatestArticles(int $limit): array
    {
        $query = new PaginationQuery(1, max(1, min(20, $limit)), '', '-createdAt', ['status' => 'published']);
        $result = $this->repository->findArticlesPaginated($query);

        return array_values($result['items']);
    }

    /**
     * @param list<Article> $articles
     * @return list<array<string, mixed>>
     */
    private function summarizeArticles(array $articles): array
    {
        $summaries = [];
        foreach ($articles as $article) {
            $summaries[] = $this->summarizeArticle($article);
        }

        return $summaries;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function summarizePopular(int $limit): array
    {
        $limit = max(1, min(20, $limit));
        $scores = $this->popularViewScores();
        arsort($scores);
        $slugs = array_slice(array_keys($scores), 0, $limit);

        if ($slugs === []) {
            return $this->summarizeArticles($this->loadLatestArticles($limit));
        }

        $summaries = [];
        foreach ($slugs as $slug) {
            $article = $this->repository->findBySlug($slug, 'article');
            if (!$article instanceof Article || $article->getStatus() !== 'published') {
                continue;
            }
            $row = $this->summarizeArticle($article);
            $row['views'] = $scores[$slug] ?? 0;
            $summaries[] = $row;
        }

        return $summaries;
    }

    /**
     * @return array<string, mixed>
     */
    private function summarizeArticle(Article $article): array
    {
        $frontMatter = $article->getFrontMatter();

        return [
            'slug' => $article->getSlug(),
            'title' => $article->getTitle(),
            'excerpt' => (string) ($frontMatter['excerpt'] ?? $frontMatter['description'] ?? ''),
            'createdAt' => (string) ($frontMatter['createdAt'] ?? ''),
            'tags' => $article->getTags(),
        ];
    }

    /**
     * @return array{
     *   enabled: bool,
     *   placement: string,
     *   showTags: bool,
     *   showCategories: bool,
     *   showLatest: bool,
     *   showPopular: bool,
     *   latestCount: int,
     *   popularCount: int
     * }
     */
    private function sidebarConfig(): array
    {
        $content = $this->settings->group('content');
        $placement = (string) ($content['blogSidebarPlacement'] ?? 'right');

        return [
            'enabled' => (bool) ($content['blogSidebarEnabled'] ?? false),
            'placement' => in_array($placement, ['left', 'right'], true) ? $placement : 'right',
            'showTags' => (bool) ($content['blogSidebarShowTags'] ?? true),
            'showCategories' => (bool) ($content['blogSidebarShowCategories'] ?? true),
            'showLatest' => (bool) ($content['blogSidebarShowLatest'] ?? true),
            'showPopular' => (bool) ($content['blogSidebarShowPopular'] ?? true),
            'latestCount' => max(1, min(20, (int) ($content['blogSidebarLatestCount'] ?? 5))),
            'popularCount' => max(1, min(20, (int) ($content['blogSidebarPopularCount'] ?? 5))),
        ];
    }

    private function extractArticleSlugFromUri(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = $uri;
        }

        if (preg_match('~/(?:blog|article|articles|clanky|posts|novinky)/([^/?#]+)~i', $path, $matches) === 1) {
            return rawurldecode($matches[1]);
        }

        return '';
    }
}
