<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\BulkBatchResult;
use PaginiumCMS\Http\Support\BulkIdsParser;
use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\Security\Firewall\FirewallService;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class FirewallController
{
    public function __construct(
        private FirewallService $firewall,
        private JsonResponder $json
    ) {
    }

    public function stats(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->firewall->stats());
    }

    public function incidents(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $limit = max(1, min(200, (int) ($params['limit'] ?? 50)));
        $offset = max(0, (int) ($params['offset'] ?? 0));

        return $this->json->success($response, [
            'items' => $this->firewall->listIncidents($limit, $offset),
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    public function bans(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $activeOnly = ($params['all'] ?? '') !== '1';

        return $this->json->success($response, $this->firewall->listBans($activeOnly));
    }

    public function createBan(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $this->parseJsonBody($request);
        $ip = trim((string) ($payload['ip'] ?? ''));
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return $this->json->error($response, 'Neplatná IP adresa', 400);
        }

        $permanent = (bool) ($payload['permanent'] ?? false);
        $reason = trim((string) ($payload['reason'] ?? 'manual'));
        if ($reason === '') {
            $reason = 'manual';
        }

        $ban = $this->firewall->manualBan($ip, $permanent, $reason);

        return $this->json->success($response, $ban, 201, 'IP adresa zablokovaná');
    }

    /**
     * @param array<string, string> $args
     */
    public function deleteBan(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $ip = urldecode((string) ($args['ip'] ?? ''));
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return $this->json->error($response, 'Neplatná IP adresa', 400);
        }

        if (!$this->firewall->unban($ip)) {
            return $this->json->error($response, 'Ban pre túto IP neexistuje', 404);
        }

        return $this->json->success($response, ['ip' => $ip], 200, 'IP adresa odblokovaná');
    }

    public function bulkUnban(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $ips = BulkIdsParser::fromRequest($request);
        if ($ips === []) {
            return $this->json->error($response, 'No IP addresses selected', 400);
        }

        $batch = new BulkBatchResult();
        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                $batch->addFailure($ip, 'Invalid IP');
                continue;
            }
            if (!$this->firewall->unban($ip)) {
                $batch->addFailure($ip, 'Ban not found');
                continue;
            }
            $batch->addSuccess($ip);
        }

        return $this->json->success($response, $batch->toArray(), 200, 'Bulk unban completed');
    }

    public function whitelist(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, [
            'ips' => $this->firewall->listWhitelist(),
        ]);
    }

    public function addWhitelist(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $this->parseJsonBody($request);
        $ip = trim((string) ($payload['ip'] ?? ''));
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return $this->json->error($response, 'Neplatná IP adresa', 400);
        }

        $this->firewall->addWhitelist($ip);

        return $this->json->success($response, ['ip' => $ip], 200, 'IP pridaná na whitelist');
    }

    /**
     * @param array<string, string> $args
     */
    public function removeWhitelist(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $ip = urldecode((string) ($args['ip'] ?? ''));
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return $this->json->error($response, 'Neplatná IP adresa', 400);
        }

        if (!$this->firewall->removeWhitelist($ip)) {
            return $this->json->error($response, 'IP nie je na whiteliste', 404);
        }

        return $this->json->success($response, ['ip' => $ip], 200, 'IP odstránená z whitelistu');
    }

    public function bulkRemoveWhitelist(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $ips = BulkIdsParser::fromRequest($request);
        if ($ips === []) {
            return $this->json->error($response, 'No IP addresses selected', 400);
        }

        $batch = new BulkBatchResult();
        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                $batch->addFailure($ip, 'Invalid IP');
                continue;
            }
            if (!$this->firewall->removeWhitelist($ip)) {
                $batch->addFailure($ip, 'Not on whitelist');
                continue;
            }
            $batch->addSuccess($ip);
        }

        return $this->json->success($response, $batch->toArray(), 200, 'Bulk whitelist removal completed');
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonBody(ServerRequestInterface $request): array
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return [];
        }

        return $data;
    }
}
