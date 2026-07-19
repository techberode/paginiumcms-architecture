<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\FlatFile\Commands;

use PaginiumCMS\Tests\Http\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use PaginiumCMS\Core\FlatFile\Commands\ContentDiagnoseCommand;

final class ContentDiagnoseCommandTest extends TestCase
{
    public function testDiagnoseRunsSuccessfullyInTesting(): void
    {
        $command = $this->app->getContainer()->get(ContentDiagnoseCommand::class);
        $application = new Application();
        $application->addCommand($command);

        $tester = new CommandTester($application->find('content:diagnose'));
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Content storage looks healthy', $tester->getDisplay());
    }

    public function testDiagnoseJsonOutputContainsHealthyFlag(): void
    {
        $command = $this->app->getContainer()->get(ContentDiagnoseCommand::class);
        $application = new Application();
        $application->addCommand($command);

        $tester = new CommandTester($application->find('content:diagnose'));
        $tester->execute(['--json' => true]);

        $decoded = json_decode($tester->getDisplay(), true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('healthy', $decoded);
    }
}
