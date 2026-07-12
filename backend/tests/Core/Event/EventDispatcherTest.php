<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Event;

use PaginiumCMS\Core\Event\EventDispatcher;
use PHPUnit\Framework\TestCase;

class EventDispatcherTest extends TestCase
{
    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = new EventDispatcher();
    }

    public function testAddListener(): void
    {
        $event = new \stdClass();
        $event->name = 'test';

        $listenerCalled = false;
        $this->dispatcher->addListener(\stdClass::class, function ($e) use (&$listenerCalled) {
            $listenerCalled = true;
            $this->assertEquals('test', $e->name);
        });

        $this->dispatcher->dispatch($event);

        $this->assertTrue($listenerCalled);
    }

    public function testMultipleListeners(): void
    {
        $event = new \stdClass();
        $counter = 0;

        $this->dispatcher->addListener(\stdClass::class, function () use (&$counter) {
            $counter++;
        });
        $this->dispatcher->addListener(\stdClass::class, function () use (&$counter) {
            $counter++;
        });

        $this->dispatcher->dispatch($event);

        $this->assertEquals(2, $counter);
    }

    public function testPriority(): void
    {
        $event = new \stdClass();
        $order = [];

        $this->dispatcher->addListener(\stdClass::class, function () use (&$order) {
            $order[] = 'first';
        }, 1);
        $this->dispatcher->addListener(\stdClass::class, function () use (&$order) {
            $order[] = 'second';
        }, 10);
        $this->dispatcher->addListener(\stdClass::class, function () use (&$order) {
            $order[] = 'third';
        }, 5);

        $this->dispatcher->dispatch($event);

        $this->assertEquals(['second', 'third', 'first'], $order);
    }

    public function testGetListeners(): void
    {
        $listeners = $this->dispatcher->getListeners(\stdClass::class);
        $this->assertEmpty($listeners);

        $this->dispatcher->addListener(\stdClass::class, function () {});
        $listeners = $this->dispatcher->getListeners(\stdClass::class);
        $this->assertCount(1, $listeners);
    }
}
