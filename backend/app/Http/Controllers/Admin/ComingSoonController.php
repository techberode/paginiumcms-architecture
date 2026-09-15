<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use InvalidArgumentException;
use PaginiumCMS\Core\ComingSoon\Services\ComingSoonRepository;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ComingSoonController
{
    public function __construct(
        private ComingSoonRepository $timers,
        private ContentRepositoryInterface $content,
        private JsonResponder $json,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, [
            'items' => $this->timers->list(),
        ]);
    }

    public function targets(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->contentTargets());
    }

    /**
     * @param array<string, string> $args
     */
    public function showPublic(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $kind = (string) ($args['kind'] ?? '');
        $slug = (string) ($args['slug'] ?? '');
        $item = $this->timers->findByKindSlug($kind, $slug);
        if ($item === null || !($item['enabled'] ?? false)) {
            return $this->json->error($response, 'Not found', 404);
        }

        return $this->json->success($response, $this->timers->presentPublic($item, time()));
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, 'Invalid payload', 400);
        }

        try {
            $this->assertContentExists($data);
            $item = $this->timers->create($data);
        } catch (InvalidArgumentException $exception) {
            $status = str_contains($exception->getMessage(), 'already exists') ? 409 : 422;

            return $this->json->error($response, $exception->getMessage(), $status);
        }

        return $this->json->success($response, ['item' => $item], 201);
    }

    /**
     * @param array<string, string> $args
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, 'Invalid payload', 400);
        }

        try {
            if (isset($data['contentKind']) || isset($data['slug'])) {
                $this->assertContentExists([
                    'contentKind' => $data['contentKind'] ?? null,
                    'slug' => $data['slug'] ?? null,
                ]);
            }
            $item = $this->timers->update((string) ($args['id'] ?? ''), $data);
        } catch (InvalidArgumentException $exception) {
            $status = str_contains($exception->getMessage(), 'not found') ? 404 : 422;
            if (str_contains($exception->getMessage(), 'already exists')) {
                $status = 409;
            }

            return $this->json->error($response, $exception->getMessage(), $status);
        }

        return $this->json->success($response, ['item' => $item]);
    }

    /**
     * @param array<string, string> $args
     */
    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $this->timers->delete((string) ($args['id'] ?? ''));
        } catch (InvalidArgumentException $exception) {
            return $this->json->error($response, $exception->getMessage(), 404);
        }

        return $this->json->success($response, ['removed' => true]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function assertContentExists(array $payload): void
    {
        $kind = is_string($payload['contentKind'] ?? null) ? $payload['contentKind'] : '';
        $slug = is_string($payload['slug'] ?? null) ? $payload['slug'] : '';
        if ($kind === '' || $slug === '') {
            throw new InvalidArgumentException('Page or article is required.');
        }

        $type = $kind === ComingSoonRepository::KIND_ARTICLE ? 'article' : 'page';
        if ($this->content->findBySlug($slug, $type) === null) {
            throw new InvalidArgumentException('Linked page or article not found');
        }
    }

    /**
     * @return array{pages: list<array{slug: string, title: string}>, articles: list<array{slug: string, title: string}>}
     */
    private function contentTargets(): array
    {
        $pages = [];
        foreach ($this->content->findAllPages() as $page) {
            $slug = $page->getSlug();
            if ($slug === '') {
                continue;
            }
            $pages[] = [
                'slug' => $slug,
                'title' => $page->getTitle() !== '' ? $page->getTitle() : $slug,
            ];
        }

        $articles = [];
        foreach ($this->content->findAllArticles() as $article) {
            $slug = $article->getSlug();
            if ($slug === '') {
                continue;
            }
            $articles[] = [
                'slug' => $slug,
                'title' => $article->getTitle() !== '' ? $article->getTitle() : $slug,
            ];
        }

        return [
            'pages' => $pages,
            'articles' => $articles,
        ];
    }
}
