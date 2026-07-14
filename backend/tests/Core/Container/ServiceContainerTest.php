<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Container;

use PaginiumCMS\Core\Container\ServiceContainer;
use PHPUnit\Framework\TestCase;

class ServiceContainerTest extends TestCase
{
    private ServiceContainer $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new ServiceContainer();
    }

    public function testSetAndGet(): void
    {
        $service = new \stdClass();
        $this->container->set('test', $service);

        $this->assertSame($service, $this->container->get('test'));
    }

    public function testSingleton(): void
    {
        $counter = 0;
        $this->container->singleton('counter', function () use (&$counter) {
            $counter++;
            return $counter;
        });

        $value1 = $this->container->get('counter');
        $value2 = $this->container->get('counter');

        $this->assertEquals(1, $value1);
        $this->assertEquals(1, $value2);
    }

    public function testAlias(): void
    {
        $service = new \stdClass();
        $this->container->set('original', $service);
        $this->container->alias('alias', 'original');

        $this->assertSame($service, $this->container->get('alias'));
    }

    public function testHas(): void
    {
        $this->assertFalse($this->container->has('test'));

        $this->container->set('test', new \stdClass());
        $this->assertTrue($this->container->has('test'));
    }

    public function testThrowsOnMissingService(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->container->get('non_existent');
    }

    public function testSingletonWithAlias(): void
    {
        $counter = 0;
        $this->container->singleton('counter', function () use (&$counter) {
            $counter++;
            return $counter;
        });
        $this->container->alias('cnt', 'counter');

        $value1 = $this->container->get('cnt');
        $value2 = $this->container->get('cnt');

        $this->assertEquals(1, $value1);
        $this->assertEquals(1, $value2);
    }
}
