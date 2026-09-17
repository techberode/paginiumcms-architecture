<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Origin;

use PaginiumCMS\Core\Security\Services\EncryptionService;
use PaginiumCMS\Modules\Origin\Services\FeatureProbeRegistry;
use PaginiumCMS\Modules\Origin\Services\ProbeSupport;
use PHPUnit\Framework\TestCase;

final class FeatureProbeRegistryTest extends TestCase
{
    public function testRunAllReturnsAtLeastTenProbes(): void
    {
        $registry = new FeatureProbeRegistry(
            new ProbeSupport(),
            new EncryptionService('base64:BGtLQwdzAE7ajivCghMa98DyudMghYZEkXKw5PJ/aUE=')
        );
        $results = $registry->runAll();

        $this->assertGreaterThanOrEqual(10, count($results));

        foreach ($results as $row) {
            $this->assertNotSame('', $row['id']);
            $this->assertContains($row['status'], ['implemented', 'partial', 'missing', 'unknown']);
        }
    }
}
