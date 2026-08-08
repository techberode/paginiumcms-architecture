<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Services\ApiScopePolicy;
use PHPUnit\Framework\TestCase;

final class ApiScopePolicyTest extends TestCase
{
    private ApiScopePolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new ApiScopePolicy();
    }

    public function testContentReadAllowsHeadlessPages(): void
    {
        $this->assertTrue($this->policy->allows('GET', '/api/headless/pages/home', ['content:read']));
    }

    public function testMissingScopeIsDenied(): void
    {
        $this->assertFalse($this->policy->allows('GET', '/api/headless/pages/home', ['settings:read']));
    }

    public function testAdminRouteIsNotAllowListed(): void
    {
        $this->assertNull($this->policy->requiredScopes('GET', '/api/admin/settings'));
    }
}
