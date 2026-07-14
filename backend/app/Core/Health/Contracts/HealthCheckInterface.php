<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Health\Contracts;

use PaginiumCMS\Core\Health\Models\HealthStatus;

interface HealthCheckInterface
{
    public function getName(): string;
    public function getDescription(): string;
    public function getGroup(): string;
    public function check(): HealthStatus;
}
