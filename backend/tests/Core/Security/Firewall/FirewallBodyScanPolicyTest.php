<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Security\Firewall;

use PaginiumCMS\Core\Security\Firewall\FirewallBodyScanPolicy;
use PHPUnit\Framework\TestCase;

final class FirewallBodyScanPolicyTest extends TestCase
{
    private FirewallBodyScanPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new FirewallBodyScanPolicy();
    }

    public function testScansPostOnNonExemptRouteWhenEnabled(): void
    {
        $this->assertTrue($this->policy->shouldScan('POST', '/api/auth/login', true));
    }

    public function testSkipsGetRequests(): void
    {
        $this->assertFalse($this->policy->shouldScan('GET', '/api/auth/login', true));
    }

    public function testSkipsWhenDisabledInSettings(): void
    {
        $this->assertFalse($this->policy->shouldScan('POST', '/api/contact', false));
    }

    public function testExemptsContentEditorRoutes(): void
    {
        $this->assertFalse($this->policy->shouldScan('POST', '/api/pages', true));
        $this->assertFalse($this->policy->shouldScan('PUT', '/api/articles/my-slug', true));
        $this->assertFalse($this->policy->shouldScan('PUT', '/api/drafts/page/foo', true));
        $this->assertFalse($this->policy->shouldScan('POST', '/api/admin/code-editor/save', true));
    }
}
