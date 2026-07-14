<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Health\Services\HealthCheckManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class HealthController
{
    private HealthCheckManager $healthManager;

    public function __construct(HealthCheckManager $healthManager)
    {
        $this->healthManager = $healthManager;
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $group = $params['group'] ?? null;

        $report = $this->healthManager->run($group);

        $response->getBody()->write(json_encode($report->toArray(), JSON_PRETTY_PRINT));
        return $response
            ->withStatus($report->isPass() ? 200 : 500)
            ->withHeader('Content-Type', 'application/json');
    }

    public function check(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $name = $request->getAttribute('name');
        $result = $this->healthManager->runCheck($name);

        if ($result === null) {
            $response->getBody()->write(json_encode([
                'error' => 'Kontrola nenájdená: ' . $name,
            ], JSON_PRETTY_PRINT));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($result->toArray(), JSON_PRETTY_PRINT));
        return $response
            ->withStatus($result->isPass() ? 200 : 500)
            ->withHeader('Content-Type', 'application/json');
    }

    public function checks(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response->getBody()->write(json_encode([
            'checks' => $this->healthManager->getAvailableChecks(),
            'groups' => $this->healthManager->getGroups(),
        ], JSON_PRETTY_PRINT));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }
}
