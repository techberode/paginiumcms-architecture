<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Commands;

use PaginiumCMS\Core\FlatFile\Commands\ContentLocaleMigrateCommand;
use PaginiumCMS\Tests\Http\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class ContentLocaleMigrateCommandTest extends TestCase
{
    public function testInventoryCommandRuns(): void
    {
        $command = $this->app->getContainer()->get(ContentLocaleMigrateCommand::class);
        $application = new Application();
        $application->addCommand($command);

        $tester = new CommandTester($application->find('content:locale-migrate'));
        $exitCode = $tester->execute(['action' => 'inventory', '--json' => true]);

        $decoded = json_decode($tester->getDisplay(), true);
        $this->assertSame(0, $exitCode);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('totals', $decoded);
    }

    public function testDryRunDoesNotRequireConfirmation(): void
    {
        $command = $this->app->getContainer()->get(ContentLocaleMigrateCommand::class);
        $application = new Application();
        $application->addCommand($command);

        $tester = new CommandTester($application->find('content:locale-migrate'));
        $exitCode = $tester->execute(['action' => 'dry-run', '--json' => true]);

        $this->assertSame(0, $exitCode);
        $decoded = json_decode($tester->getDisplay(), true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('wouldConvert', $decoded);
    }
}
