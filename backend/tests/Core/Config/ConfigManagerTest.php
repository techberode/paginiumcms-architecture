<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Config;

use PaginiumCMS\Core\Config\ConfigManager;
use PHPUnit\Framework\TestCase;

class ConfigManagerTest extends TestCase
{
    private ConfigManager $configManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configManager = new ConfigManager();
    }

    public function testSetAndGet(): void
    {
        $this->configManager->set('app.name', 'PaginiumCMS');
        $this->assertEquals('PaginiumCMS', $this->configManager->get('app.name'));
    }

    public function testGetWithDefault(): void
    {
        $this->assertEquals('default', $this->configManager->get('non.existent', 'default'));
    }

    public function testMerge(): void
    {
        $this->configManager->set('app.name', 'PaginiumCMS');
        $this->configManager->set('app.version', '2.0');
        $this->assertEquals('PaginiumCMS', $this->configManager->get('app.name'));
        $this->assertEquals('2.0', $this->configManager->get('app.version'));

        $this->configManager->set('app.name', 'NewName');
        $this->assertEquals('NewName', $this->configManager->get('app.name'));
    }

    public function testNestedGet(): void
    {
        $this->configManager->set('database.host', 'localhost');
        $this->configManager->set('database.port', 3306);
        $this->assertEquals('localhost', $this->configManager->get('database.host'));
        $this->assertEquals(3306, $this->configManager->get('database.port'));
    }
}
