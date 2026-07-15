<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Health\Services;

use PaginiumCMS\Core\Health\Contracts\HealthCheckInterface;
use PaginiumCMS\Core\Health\Models\HealthReport;
use PaginiumCMS\Core\Health\Models\HealthStatus;

class HealthCheckManager
{
    private array $checks = [];

    public function addCheck(HealthCheckInterface $check): void
    {
        $this->checks[] = $check;
    }

    public function addChecks(array $checks): void
    {
        foreach ($checks as $check) {
            if ($check instanceof HealthCheckInterface) {
                $this->addCheck($check);
            }
        }
    }

    public function run(?string $group = null): HealthReport
    {
        $report = new HealthReport();

        foreach ($this->checks as $check) {
            if ($group && $check->getGroup() !== $group) {
                continue;
            }

            $result = $check->check();
            $report->addCheck($result);
        }

        return $report;
    }

    public function runCheck(string $name): ?HealthStatus
    {
        foreach ($this->checks as $check) {
            if ($check->getName() === $name) {
                return $check->check();
            }
        }
        return null;
    }

    public function getAvailableChecks(): array
    {
        return array_map(function ($check) {
            return [
                'name' => $check->getName(),
                'description' => $check->getDescription(),
                'group' => $check->getGroup(),
            ];
        }, $this->checks);
    }

    public function getGroups(): array
    {
        $groups = [];
        foreach ($this->checks as $check) {
            $groups[$check->getGroup()][] = $check->getName();
        }
        return $groups;
    }
}
