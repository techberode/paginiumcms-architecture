<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Hook;

use PaginiumCMS\Core\Hook\HookManager;
use PHPUnit\Framework\TestCase;

class HookManagerTest extends TestCase
{
    private HookManager $hookManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hookManager = new HookManager();
    }

    public function testAddHook(): void
    {
        $this->hookManager->add('test_hook', function () {});
        $this->assertTrue($this->hookManager->has('test_hook'));
    }

    public function testRunHook(): void
    {
        $result = null;
        $this->hookManager->add('test_hook', function ($value) use (&$result) {
            $result = $value;
            return $value;
        });

        $results = $this->hookManager->run('test_hook', ['test_value']);
        $this->assertEquals('test_value', $result);
        $this->assertCount(1, $results);
    }

    public function testMultipleHooks(): void
    {
        $results = [];
        $this->hookManager->add('test_hook', function ($value) use (&$results) {
            $results[] = $value . '_1';
            return $value . '_1';
        });
        $this->hookManager->add('test_hook', function ($value) use (&$results) {
            $results[] = $value . '_2';
            return $value . '_2';
        });

        $returned = $this->hookManager->run('test_hook', ['test']);

        $this->assertCount(2, $returned);
        $this->assertEquals('test_1', $returned[0]);
        $this->assertEquals('test_2', $returned[1]);
    }

    public function testRunFirst(): void
    {
        $this->hookManager->add('test_hook', function ($value) {
            return $value . '_first';
        });
        $this->hookManager->add('test_hook', function ($value) {
            return $value . '_second';
        });

        $result = $this->hookManager->runFirst('test_hook', ['test']);
        $this->assertEquals('test_first', $result);
    }

    public function testRemoveHook(): void
    {
        $this->hookManager->add('test_hook', function () {});
        $this->assertTrue($this->hookManager->has('test_hook'));

        $this->hookManager->remove('test_hook');
        $this->assertFalse($this->hookManager->has('test_hook'));
    }
}
