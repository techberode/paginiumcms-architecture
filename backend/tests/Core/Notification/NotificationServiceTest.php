<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Notification;

use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Notification\Adapters\AdapterInterface;
use PHPUnit\Framework\TestCase;

class NotificationServiceTest extends TestCase
{
    private NotificationService $service;
    private AdapterInterface $mockAdapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new NotificationService();
        $this->mockAdapter = $this->createMock(AdapterInterface::class);
    }

    public function testAddAdapter(): void
    {
        $this->service->addAdapter('test', $this->mockAdapter);
        $adapters = $this->service->getAdapters();

        $this->assertContains('test', $adapters);
    }

    public function testSend(): void
    {
        $this->mockAdapter->expects($this->once())
            ->method('send')
            ->with('test@example.com', 'Subject', 'Message', [])
            ->willReturn(true);

        $this->service->addAdapter('email', $this->mockAdapter);
        $result = $this->service->send('email', 'test@example.com', 'Subject', 'Message');

        $this->assertTrue($result);
    }

    public function testSendToAll(): void
    {
        $adapter1 = $this->createMock(AdapterInterface::class);
        $adapter2 = $this->createMock(AdapterInterface::class);

        $adapter1->expects($this->once())
            ->method('send')
            ->willReturn(true);

        $adapter2->expects($this->once())
            ->method('send')
            ->willReturn(false);

        $this->service->addAdapter('adapter1', $adapter1);
        $this->service->addAdapter('adapter2', $adapter2);

        $results = $this->service->sendToAll('test@example.com', 'Subject', 'Message');

        $this->assertTrue($results['adapter1']);
        $this->assertFalse($results['adapter2']);
    }
}
