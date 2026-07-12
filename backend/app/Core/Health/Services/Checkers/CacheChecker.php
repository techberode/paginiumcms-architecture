<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Health\Services\Checkers;

use PaginiumCMS\Core\Health\Contracts\HealthCheckInterface;
use PaginiumCMS\Core\Health\Models\HealthStatus;
use PaginiumCMS\Core\Cache\CacheManager;

class CacheChecker implements HealthCheckInterface
{
    private CacheManager $cache;

    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
    }

    public function getName(): string { return 'cache'; }
    public function getDescription(): string { return 'Kontrola cache systému'; }
    public function getGroup(): string { return 'performance'; }

    public function check(): HealthStatus
    {
        $start = microtime(true);
        $issues = [];
        $data = [];

        try {
            // 1. Test zápisu
            $testKey = 'health_check_' . uniqid();
            $testValue = 'test_value_' . time();

            $writeResult = $this->cache->set($testKey, $testValue, 60);
            $data['write'] = $writeResult;

            if (!$writeResult) {
                $issues[] = 'Zápis do cache zlyhal';
            }

            // 2. Test čítania
            $readValue = $this->cache->get($testKey);
            $data['read'] = $readValue === $testValue;

            if ($readValue !== $testValue) {
                $issues[] = 'Čítanie z cache zlyhalo';
            }

            // 3. Test mazania
            $deleteResult = $this->cache->delete($testKey);
            $data['delete'] = $deleteResult;

            if (!$deleteResult) {
                $issues[] = 'Mazanie z cache zlyhalo';
            }

            $data['driver'] = get_class($this->cache);
        } catch (\Exception $e) {
            $issues[] = 'Cache chyba: ' . $e->getMessage();
            $data['error'] = $e->getMessage();
        }

        $status = empty($issues) ? HealthStatus::STATUS_PASS : HealthStatus::STATUS_FAIL;
        $message = empty($issues) ? 'Cache funguje správne' : implode(', ', $issues);

        $check = new HealthStatus($this->getName(), $status, $message);
        $check->setData($data);
        $check->setDuration(microtime(true) - $start);

        return $check;
    }
}
