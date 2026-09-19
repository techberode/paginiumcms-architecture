<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Layout;

use PaginiumCMS\Core\Layout\Services\StaffCardRenderer;
use PHPUnit\Framework\TestCase;

final class StaffCardRendererTest extends TestCase
{
    public function testRendersUserIsland(): void
    {
        $html = StaffCardRenderer::render('staff-card', ['user' => 'ada@example.com']);

        $this->assertStringContainsString('pg-staff-cards', $html);
        $this->assertStringContainsString('data-staff-mode="user"', $html);
        $this->assertStringContainsString('data-staff-user="ada@example.com"', $html);
    }

    public function testRendersTeamIsland(): void
    {
        $html = StaffCardRenderer::render('staff-team', ['id' => 'team_abc', 'type' => 'support']);

        $this->assertStringContainsString('data-staff-mode="team"', $html);
        $this->assertStringContainsString('data-staff-team="team_abc"', $html);
    }
}
