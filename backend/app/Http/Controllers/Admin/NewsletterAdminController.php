<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class NewsletterAdminController
{
    public function __construct(
        private NewsletterRepositoryInterface $newsletterRepository,
        private JsonResponder $json
    ) {
    }

    public function listSubscribers(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $items = $this->newsletterRepository->findAll();

        return $this->json->success($response, [
            'items' => $items,
            'count' => count($items),
            'bySource' => $this->newsletterRepository->countBySource(),
        ]);
    }

    public function exportSubscribers(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $csv = $this->newsletterRepository->exportCsv();
        $response->getBody()->write($csv);

        return $response
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="newsletter_subscribers_' . date('Y-m-d') . '.csv"');
    }
}
