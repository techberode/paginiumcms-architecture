<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\Admin;

use PaginiumCMS\Tests\Http\TestCase;

final class SupportKanbanControllerTest extends TestCase
{
    public function testIndexRequiresAuth(): void
    {
        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/admin/support-kanban'));
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testIndexReturnsBoard(): void
    {
        $this->loginAsAdminUser();
        $response = $this->handleRequest($this->createJsonRequest('GET', '/api/admin/support-kanban'));
        $this->assertSame(200, $response->getStatusCode());
        $payload = $this->getJsonResponse($response);
        $this->assertTrue($payload['success'] ?? false);
        $this->assertSame('support-board@1', $payload['data']['board']['schema'] ?? null);
        $this->assertIsArray($payload['data']['tickets'] ?? null);
        $this->assertIsArray($payload['data']['agents'] ?? null);
        $this->assertIsArray($payload['data']['cannedReplies'] ?? null);
    }

    public function testCannedAndNoteRequireAuth(): void
    {
        $canned = $this->handleRequest($this->createJsonRequest('PUT', '/api/admin/support-kanban/canned', [
            'replies' => [['title' => 'Hi', 'body' => 'Hello']],
        ]));
        $this->assertSame(401, $canned->getStatusCode());

        $note = $this->handleRequest($this->createJsonRequest(
            'POST',
            '/api/admin/support-kanban/tickets/tkt_aaaaaaaaaa/notes',
            ['body' => 'secret']
        ));
        $this->assertSame(401, $note->getStatusCode());
    }

    public function testSaveCannedAndAddNote(): void
    {
        $this->loginAsAdminUser();
        $create = $this->handleRequest($this->createJsonRequest('POST', '/api/admin/support-kanban/tickets', [
            'subject' => 'Canned path',
            'dueAt' => 1_800_000_000,
        ]));
        $this->assertSame(201, $create->getStatusCode());
        $ticketId = $this->getJsonResponse($create)['data']['ticket']['id'] ?? '';
        $this->assertIsString($ticketId);
        $this->assertSame(1_800_000_000, $this->getJsonResponse($create)['data']['ticket']['dueAt'] ?? null);

        $canned = $this->handleRequest($this->createJsonRequest('PUT', '/api/admin/support-kanban/canned', [
            'replies' => [['title' => 'Need logs', 'body' => 'Please attach the error log.']],
        ]));
        $this->assertSame(200, $canned->getStatusCode());
        $replies = $this->getJsonResponse($canned)['data']['cannedReplies'] ?? null;
        $this->assertIsArray($replies);
        $this->assertSame('Need logs', $replies[0]['title'] ?? null);

        $note = $this->handleRequest($this->createJsonRequest(
            'POST',
            '/api/admin/support-kanban/tickets/' . $ticketId . '/notes',
            ['body' => 'Called the customer']
        ));
        $this->assertSame(201, $note->getStatusCode());
        $notes = $this->getJsonResponse($note)['data']['ticket']['internalNotes'] ?? null;
        $this->assertIsArray($notes);
        $this->assertSame('Called the customer', $notes[0]['body'] ?? null);
        $this->assertNotSame('', $notes[0]['authorUserId'] ?? '');
    }
}
