<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Support\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Support Kanban board + tickets (It.93l) and 93l-2 notes / canned replies / SLA.
 * Per-team board: data/team-kanban/{teamId}/board.json (`support-board@1`).
 * Legacy global paths remain for {@see self::LEGACY_SCOPE} (support-kanban API).
 */
final class SupportKanbanRepository
{
    /** Use with team-scoped APIs to read/write legacy global storage (It.93l). */
    public const LEGACY_SCOPE = '__legacy__';

    private const LEGACY_BOARD = 'data/support-board.json';
    private const LEGACY_TICKET_DIR = 'data/support-tickets';
    private const LEGACY_CANNED = 'data/support-canned.json';

    public const BOARD_SCHEMA = 'support-board@1';
    public const TICKET_SCHEMA = 'support-ticket@1';
    public const CANNED_SCHEMA = 'support-canned@1';

    public const MAX_TICKETS = 400;
    public const MAX_COLUMNS = 12;
    public const MAX_LABELS = 24;
    public const MAX_SUBJECT = 160;
    public const MAX_BODY = 20000;
    public const MAX_NAME = 120;
    public const MAX_EMAIL = 180;
    public const MAX_MESSAGE_ID = 64;
    public const MAX_LABEL_NAME = 32;
    public const MAX_COLUMN_LABEL = 40;
    public const MAX_NOTES = 80;
    public const MAX_NOTE_BODY = 4000;
    public const MAX_CANNED = 40;
    public const MAX_CANNED_TITLE = 80;
    public const MAX_CANNED_BODY = 4000;

    private string $boardPath = self::LEGACY_BOARD;
    private string $ticketDir = self::LEGACY_TICKET_DIR;
    private string $cannedPath = self::LEGACY_CANNED;

    private string $activeTeamId = self::LEGACY_SCOPE;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
    ) {
    }

    /**
     * @return array{schema: string, columns: list<array<string, mixed>>, labels: list<array<string, mixed>>, statsEnabled?: bool}
     */
    public function getBoard(string $teamId): array
    {
        $this->bindTeam($teamId);
        $board = $this->readBoard();
        if ($board !== null) {
            return $board;
        }

        return $this->writeBoard($this->defaultBoard());
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{schema: string, columns: list<array<string, mixed>>, labels: list<array<string, mixed>>, statsEnabled?: bool}
     */
    public function saveBoard(string $teamId, array $payload): array
    {
        $this->bindTeam($teamId);
        $current = $this->getBoard($teamId);
        $columns = $this->normalizeColumns($payload['columns'] ?? $current['columns']);
        if ($columns === []) {
            throw new InvalidArgumentException('At least one column is required.');
        }

        $labels = $this->normalizeLabels($payload['labels'] ?? $current['labels']);
        $keptColumnIds = [];
        foreach ($columns as $column) {
            $keptColumnIds[] = (string) $column['id'];
        }
        $fallback = $keptColumnIds[0];
        $keptLabelIds = [];
        foreach ($labels as $label) {
            $keptLabelIds[] = (string) $label['id'];
        }

        $statsEnabled = array_key_exists('statsEnabled', $payload)
            ? (bool) $payload['statsEnabled']
            : (bool) ($current['statsEnabled'] ?? false);

        $board = $this->writeBoard([
            'schema' => self::BOARD_SCHEMA,
            'columns' => $columns,
            'labels' => $labels,
            'statsEnabled' => $statsEnabled,
        ]);

        foreach ($this->listTickets($teamId) as $ticket) {
            $columnId = (string) $ticket['columnId'];
            $ids = is_array($ticket['labelIds'] ?? null) ? $ticket['labelIds'] : [];
            $filtered = [];
            foreach ($ids as $labelId) {
                if (is_string($labelId) && in_array($labelId, $keptLabelIds, true)) {
                    $filtered[] = $labelId;
                }
            }
            $patch = [];
            if (!in_array($columnId, $keptColumnIds, true)) {
                $patch['columnId'] = $fallback;
            }
            if ($filtered !== $ids) {
                $patch['labelIds'] = $filtered;
            }
            if ($patch !== []) {
                $this->updateTicket($teamId, (string) $ticket['id'], $patch);
            }
        }

        return $board;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listTickets(string $teamId): array
    {
        $this->bindTeam($teamId);
        $items = [];
        foreach ($this->ticketFiles() as $file) {
            $id = basename($file, '.json');
            $ticket = $this->readTicket($id);
            if ($ticket !== null) {
                $items[] = $ticket;
            }
        }

        usort(
            $items,
            static function (array $a, array $b): int {
                $order = ((int) $a['order']) <=> ((int) $b['order']);
                if ($order !== 0) {
                    return $order;
                }

                return ((int) $b['updatedAt']) <=> ((int) $a['updatedAt']);
            }
        );

        return $items;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getTicket(string $teamId, string $id): ?array
    {
        $this->bindTeam($teamId);
        try {
            return $this->readTicket($this->normalizeTicketId($id));
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $agentIds
     * @return array<string, mixed>
     */
    public function createTicket(string $teamId, array $payload, array $agentIds): array
    {
        $this->bindTeam($teamId);
        if (count($this->listTickets($teamId)) >= self::MAX_TICKETS) {
            throw new InvalidArgumentException('Too many tickets.');
        }

        $board = $this->getBoard($teamId);
        $columnIds = [];
        foreach ($board['columns'] as $column) {
            $columnIds[] = (string) $column['id'];
        }
        $columnId = is_string($payload['columnId'] ?? null) ? $payload['columnId'] : '';
        if ($columnId === '' || !in_array($columnId, $columnIds, true)) {
            $columnId = $columnIds[0];
        }
        $this->assertColumnWip($columnId);

        $now = time();
        $id = 'tkt_' . bin2hex(random_bytes(5));

        return $this->writeTicket([
            'schema' => self::TICKET_SCHEMA,
            'id' => $id,
            'subject' => $this->normalizeSubject(is_string($payload['subject'] ?? null) ? $payload['subject'] : ''),
            'body' => $this->normalizeBody(is_string($payload['body'] ?? null) ? $payload['body'] : ''),
            'columnId' => $columnId,
            'order' => $this->normalizeOrder($payload['order'] ?? $this->nextOrder($columnId)),
            'assigneeUserId' => $this->normalizeAssignee(
                is_string($payload['assigneeUserId'] ?? null) ? $payload['assigneeUserId'] : '',
                $agentIds
            ),
            'requesterName' => $this->normalizeName(
                is_string($payload['requesterName'] ?? null) ? $payload['requesterName'] : ''
            ),
            'requesterEmail' => $this->normalizeEmail(
                is_string($payload['requesterEmail'] ?? null) ? $payload['requesterEmail'] : ''
            ),
            'messageId' => $this->normalizeMessageId(
                is_string($payload['messageId'] ?? null) ? $payload['messageId'] : ''
            ),
            'labelIds' => $this->normalizeTicketLabels($payload['labelIds'] ?? [], $board),
            'dueAt' => $this->normalizeDueAt($payload['dueAt'] ?? null),
            'internalNotes' => [],
            'createdAt' => $now,
            'updatedAt' => $now,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $agentIds
     * @return array<string, mixed>
     */
    public function updateTicket(string $teamId, string $id, array $payload, array $agentIds = []): array
    {
        $this->bindTeam($teamId);
        $id = $this->normalizeTicketId($id);
        $existing = $this->readTicket($id);
        if ($existing === null) {
            throw new InvalidArgumentException('Ticket not found');
        }

        $board = $this->getBoard($teamId);
        $columnIds = [];
        foreach ($board['columns'] as $column) {
            $columnIds[] = (string) $column['id'];
        }

        if (array_key_exists('subject', $payload)) {
            $existing['subject'] = $this->normalizeSubject(is_string($payload['subject']) ? $payload['subject'] : '');
        }
        if (array_key_exists('body', $payload)) {
            $existing['body'] = $this->normalizeBody(is_string($payload['body']) ? $payload['body'] : '');
        }
        if (array_key_exists('columnId', $payload)) {
            $columnId = is_string($payload['columnId']) ? $payload['columnId'] : '';
            if (!in_array($columnId, $columnIds, true)) {
                throw new InvalidArgumentException('Unknown column.');
            }
            $previousColumn = (string) ($existing['columnId'] ?? '');
            if ($columnId !== $previousColumn) {
                $this->assertColumnWip($columnId, $id);
            }
            $existing['columnId'] = $columnId;
            if ($this->columnIsTerminal($columnId)) {
                $existing['completedAt'] = time();
            } elseif ($this->columnIsTerminal($previousColumn)) {
                $existing['completedAt'] = null;
            }
        }
        if (array_key_exists('order', $payload)) {
            $existing['order'] = $this->normalizeOrder($payload['order']);
        }
        if (array_key_exists('assigneeUserId', $payload)) {
            $existing['assigneeUserId'] = $this->normalizeAssignee(
                is_string($payload['assigneeUserId']) ? $payload['assigneeUserId'] : '',
                $agentIds
            );
        }
        if (array_key_exists('requesterName', $payload)) {
            $existing['requesterName'] = $this->normalizeName(
                is_string($payload['requesterName']) ? $payload['requesterName'] : ''
            );
        }
        if (array_key_exists('requesterEmail', $payload)) {
            $existing['requesterEmail'] = $this->normalizeEmail(
                is_string($payload['requesterEmail']) ? $payload['requesterEmail'] : ''
            );
        }
        if (array_key_exists('messageId', $payload)) {
            $existing['messageId'] = $this->normalizeMessageId(
                is_string($payload['messageId']) ? $payload['messageId'] : ''
            );
        }
        if (array_key_exists('labelIds', $payload)) {
            $existing['labelIds'] = $this->normalizeTicketLabels($payload['labelIds'], $board);
        }
        if (array_key_exists('dueAt', $payload)) {
            $existing['dueAt'] = $this->normalizeDueAt($payload['dueAt']);
        }

        $existing['updatedAt'] = time();

        return $this->writeTicket($existing);
    }

    /**
     * Append-only staff note. Author is always the session user — never from the payload.
     *
     * @return array<string, mixed>
     */
    public function addInternalNote(string $teamId, string $id, string $body, string $authorUserId): array
    {
        $this->bindTeam($teamId);
        $id = $this->normalizeTicketId($id);
        $existing = $this->readTicket($id);
        if ($existing === null) {
            throw new InvalidArgumentException('Ticket not found');
        }

        $notes = $this->normalizeStoredNotes($existing['internalNotes'] ?? []);
        if (count($notes) >= self::MAX_NOTES) {
            throw new InvalidArgumentException('Too many internal notes.');
        }

        $authorUserId = trim($authorUserId);
        if ($authorUserId === '') {
            throw new InvalidArgumentException('Note author is required.');
        }

        $notes[] = [
            'id' => 'nte_' . bin2hex(random_bytes(5)),
            'body' => $this->normalizeNoteBody($body),
            'authorUserId' => mb_substr($authorUserId, 0, 64),
            'createdAt' => time(),
        ];
        $existing['internalNotes'] = $notes;
        $existing['updatedAt'] = time();

        return $this->writeTicket($existing);
    }

    /**
     * Cycle-time stats for closed tickets (createdAt → completedAt).
     *
     * @return array{
     *   openCount: int,
     *   completedCount: int,
     *   avgSeconds: int|null,
     *   medianSeconds: int|null
     * }
     */
    public function stats(string $teamId): array
    {
        $this->bindTeam($teamId);
        $openCount = 0;
        $durations = [];
        foreach ($this->listTicketsInScope() as $ticket) {
            $completedAt = is_int($ticket['completedAt'] ?? null) ? $ticket['completedAt'] : 0;
            $createdAt = is_int($ticket['createdAt'] ?? null) ? $ticket['createdAt'] : 0;
            if ($completedAt > 0 && $createdAt > 0 && $completedAt >= $createdAt) {
                $durations[] = $completedAt - $createdAt;
                continue;
            }
            $openCount++;
        }

        sort($durations);
        $completedCount = count($durations);
        $avgSeconds = $completedCount > 0 ? (int) round(array_sum($durations) / $completedCount) : null;
        $medianSeconds = null;
        if ($completedCount > 0) {
            $mid = (int) floor($completedCount / 2);
            $medianSeconds = $completedCount % 2 === 1
                ? $durations[$mid]
                : (int) round(($durations[$mid - 1] + $durations[$mid]) / 2);
        }

        return [
            'openCount' => $openCount,
            'completedCount' => $completedCount,
            'avgSeconds' => $avgSeconds,
            'medianSeconds' => $medianSeconds,
        ];
    }

    /**
     * @return array{schema: string, replies: list<array{id: string, title: string, body: string}>}
     */
    public function getCannedReplies(string $teamId): array
    {
        $this->bindTeam($teamId);
        $stored = $this->readCanned();
        if ($stored !== null) {
            return $stored;
        }

        return $this->writeCanned(['schema' => self::CANNED_SCHEMA, 'replies' => []]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{schema: string, replies: list<array{id: string, title: string, body: string}>}
     */
    public function saveCannedReplies(string $teamId, array $payload): array
    {
        $this->bindTeam($teamId);
        $raw = $payload['replies'] ?? $payload;

        return $this->writeCanned([
            'schema' => self::CANNED_SCHEMA,
            'replies' => $this->normalizeCannedReplies($raw),
        ]);
    }

    public function deleteTicket(string $teamId, string $id): void
    {
        $this->bindTeam($teamId);
        $id = $this->normalizeTicketId($id);
        $relativePath = $this->ticketPath($id);
        if (!$this->reader->exists($relativePath)) {
            throw new InvalidArgumentException('Ticket not found');
        }

        $this->writer->delete($relativePath, false);
    }

    public function clearAssignee(string $teamId, string $userId): void
    {
        $this->bindTeam($teamId);
        $userId = trim($userId);
        if ($userId === '') {
            return;
        }

        foreach ($this->listTicketsInScope() as $ticket) {
            if ((string) ($ticket['assigneeUserId'] ?? '') === $userId) {
                $this->updateTicket($teamId, (string) $ticket['id'], ['assigneeUserId' => '']);
            }
        }
    }

    private function bindTeam(string $teamId): void
    {
        if ($teamId === self::LEGACY_SCOPE) {
            $this->activeTeamId = self::LEGACY_SCOPE;
            $this->boardPath = self::LEGACY_BOARD;
            $this->ticketDir = self::LEGACY_TICKET_DIR;
            $this->cannedPath = self::LEGACY_CANNED;

            return;
        }

        $teamId = $this->normalizeTeamId($teamId);
        $this->activeTeamId = $teamId;
        $base = 'data/team-kanban/' . $teamId;
        $this->boardPath = $base . '/board.json';
        $this->ticketDir = $base . '/tickets';
        $this->cannedPath = $base . '/canned.json';
        $this->importLegacyIfEmpty($teamId);
    }

    private function normalizeTeamId(string $id): string
    {
        $id = strtolower(trim($id));
        if ($id === '' || !preg_match('/^team_[a-f0-9]{10}$/', $id)) {
            throw new InvalidArgumentException('Invalid team id.');
        }

        return $id;
    }

    private function importLegacyIfEmpty(string $teamId): void
    {
        if ($this->reader->exists($this->boardPath) || !$this->reader->exists(self::LEGACY_BOARD)) {
            return;
        }

        $this->writer->createDirectory('data/team-kanban/' . $teamId);
        $this->writer->createDirectory($this->ticketDir);
        $this->copyFile(self::LEGACY_BOARD, $this->boardPath);
        if ($this->reader->exists(self::LEGACY_CANNED)) {
            $this->copyFile(self::LEGACY_CANNED, $this->cannedPath);
        }
        foreach ($this->legacyTicketFiles() as $file) {
            $name = basename($file);
            $this->copyFile(self::LEGACY_TICKET_DIR . '/' . $name, $this->ticketDir . '/' . $name);
        }
    }

    private function copyFile(string $from, string $to): void
    {
        if (!$this->reader->exists($from)) {
            return;
        }
        $this->writer->write($to, $this->reader->read($from), false);
    }

    /**
     * @return list<string>
     */
    private function legacyTicketFiles(): array
    {
        try {
            $files = $this->reader->listFiles(self::LEGACY_TICKET_DIR, '*.json');
        } catch (\Throwable) {
            return [];
        }

        return array_values($files);
    }

    /**
     * @return array{schema: string, columns: list<array<string, mixed>>, labels: list<array<string, mixed>>, statsEnabled?: bool}
     */
    private function boardInScope(): array
    {
        $board = $this->readBoard();
        if ($board !== null) {
            return $board;
        }

        return $this->writeBoard($this->defaultBoard());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function listTicketsInScope(): array
    {
        return $this->listTickets($this->activeTeamId);
    }

    /**
     * @return array{schema: string, columns: list<array<string, mixed>>, labels: list<array<string, mixed>>, statsEnabled?: bool}
     */
    private function defaultBoard(): array
    {
        return [
            'schema' => self::BOARD_SCHEMA,
            'columns' => [
                ['id' => 'open', 'label' => 'Open', 'color' => '#2c7be5', 'wipLimit' => 0],
                ['id' => 'pending', 'label' => 'Pending', 'color' => '#f59e0b', 'wipLimit' => 0],
                ['id' => 'closed', 'label' => 'Closed', 'color' => '#10b981', 'wipLimit' => 0],
            ],
            'labels' => [],
            'statsEnabled' => false,
        ];
    }

    /**
     * @return array{schema: string, columns: list<array<string, mixed>>, labels: list<array<string, mixed>>, statsEnabled?: bool}|null
     */
    private function readBoard(): ?array
    {
        if (!$this->reader->exists($this->boardPath)) {
            return null;
        }

        try {
            $data = JsonHelper::decode($this->reader->read($this->boardPath));
        } catch (\Throwable) {
            return null;
        }

        try {
            return [
                'schema' => self::BOARD_SCHEMA,
                'columns' => $this->normalizeColumns($data['columns'] ?? null),
                'labels' => $this->normalizeLabels($data['labels'] ?? null),
                'statsEnabled' => (bool) ($data['statsEnabled'] ?? false),
            ];
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @param array{schema: string, columns: list<array<string, mixed>>, labels: list<array<string, mixed>>, statsEnabled?: bool} $board
     * @return array{schema: string, columns: list<array<string, mixed>>, labels: list<array<string, mixed>>, statsEnabled?: bool}
     */
    private function writeBoard(array $board): array
    {
        $canonical = [
            'schema' => self::BOARD_SCHEMA,
            'columns' => $this->normalizeColumns($board['columns']),
            'labels' => $this->normalizeLabels($board['labels']),
            'statsEnabled' => (bool) ($board['statsEnabled'] ?? false),
        ];
        $this->writer->write(
            $this->boardPath,
            JsonHelper::encode($canonical, JSON_PRETTY_PRINT),
            false
        );

        return $canonical;
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function writeTicket(array $record): array
    {
        $id = $this->normalizeTicketId((string) ($record['id'] ?? ''));
        $canonical = [
            'schema' => self::TICKET_SCHEMA,
            'id' => $id,
            'subject' => $this->normalizeSubject((string) ($record['subject'] ?? '')),
            'body' => $this->normalizeBody((string) ($record['body'] ?? '')),
            'columnId' => $this->normalizeColumnRef((string) ($record['columnId'] ?? '')),
            'order' => $this->normalizeOrder($record['order'] ?? 0),
            'assigneeUserId' => is_string($record['assigneeUserId'] ?? null) && $record['assigneeUserId'] !== ''
                ? $record['assigneeUserId']
                : null,
            'requesterName' => $this->normalizeName((string) ($record['requesterName'] ?? '')),
            'requesterEmail' => (string) ($record['requesterEmail'] ?? ''),
            'messageId' => $this->normalizeMessageId((string) ($record['messageId'] ?? '')),
            'labelIds' => is_array($record['labelIds'] ?? null) ? array_values($record['labelIds']) : [],
            'dueAt' => is_int($record['dueAt'] ?? null) ? $record['dueAt'] : null,
            'internalNotes' => $this->normalizeStoredNotes($record['internalNotes'] ?? []),
            'createdAt' => is_int($record['createdAt'] ?? null) ? $record['createdAt'] : time(),
            'updatedAt' => is_int($record['updatedAt'] ?? null) ? $record['updatedAt'] : time(),
            'completedAt' => is_int($record['completedAt'] ?? null) ? $record['completedAt'] : null,
        ];

        $this->writer->createDirectory($this->ticketDir);
        $this->writer->write(
            $this->ticketPath($id),
            JsonHelper::encode($canonical, JSON_PRETTY_PRINT),
            false
        );

        $saved = $this->readTicket($id);
        if ($saved === null) {
            throw new RuntimeException('Ticket was not stored.');
        }

        return $saved;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readTicket(string $id): ?array
    {
        $relativePath = $this->ticketPath($id);
        if (!$this->reader->exists($relativePath)) {
            return null;
        }

        try {
            $data = JsonHelper::decode($this->reader->read($relativePath));
        } catch (\Throwable) {
            return null;
        }

        $storedId = is_string($data['id'] ?? null) ? $data['id'] : $id;
        if ($storedId !== $id) {
            return null;
        }

        $labelIds = [];
        if (is_array($data['labelIds'] ?? null)) {
            foreach ($data['labelIds'] as $labelId) {
                if (is_string($labelId) && $labelId !== '') {
                    $labelIds[] = $labelId;
                }
            }
        }

        return [
            'schema' => self::TICKET_SCHEMA,
            'id' => $id,
            'subject' => $this->normalizeSubject(is_string($data['subject'] ?? null) ? $data['subject'] : ''),
            'body' => $this->normalizeBody(is_string($data['body'] ?? null) ? $data['body'] : ''),
            'columnId' => $this->normalizeColumnRef(is_string($data['columnId'] ?? null) ? $data['columnId'] : 'open'),
            'order' => $this->normalizeOrder($data['order'] ?? 0),
            'assigneeUserId' => is_string($data['assigneeUserId'] ?? null) && $data['assigneeUserId'] !== ''
                ? $data['assigneeUserId']
                : null,
            'requesterName' => $this->normalizeName(is_string($data['requesterName'] ?? null) ? $data['requesterName'] : ''),
            'requesterEmail' => is_string($data['requesterEmail'] ?? null) ? $data['requesterEmail'] : '',
            'messageId' => $this->normalizeMessageId(is_string($data['messageId'] ?? null) ? $data['messageId'] : ''),
            'labelIds' => $labelIds,
            'dueAt' => is_int($data['dueAt'] ?? null) ? $data['dueAt'] : null,
            'internalNotes' => $this->normalizeStoredNotes($data['internalNotes'] ?? []),
            'createdAt' => is_int($data['createdAt'] ?? null) ? $data['createdAt'] : 0,
            'updatedAt' => is_int($data['updatedAt'] ?? null) ? $data['updatedAt'] : 0,
            'completedAt' => is_int($data['completedAt'] ?? null) ? $data['completedAt'] : null,
        ];
    }

    private function columnIsTerminal(string $columnId): bool
    {
        $board = $this->boardInScope();
        foreach ($board['columns'] as $column) {
            if ((string) ($column['id'] ?? '') !== $columnId) {
                continue;
            }
            $id = strtolower((string) ($column['id'] ?? ''));
            $label = strtolower((string) ($column['label'] ?? ''));

            return $id === 'closed'
                || $id === 'done'
                || $id === 'resolved'
                || str_contains($label, 'closed')
                || str_contains($label, 'done')
                || str_contains($label, 'uzav')
                || str_contains($label, 'hotov');
        }

        return false;
    }

    private function assertColumnWip(string $columnId, ?string $excludeTicketId = null): void
    {
        $limit = 0;
        foreach ($this->boardInScope()['columns'] as $column) {
            if ((string) ($column['id'] ?? '') === $columnId) {
                $limit = (int) ($column['wipLimit'] ?? 0);
                break;
            }
        }
        if ($limit < 1) {
            return;
        }

        $count = 0;
        foreach ($this->listTicketsInScope() as $ticket) {
            if ((string) ($ticket['columnId'] ?? '') !== $columnId) {
                continue;
            }
            if ($excludeTicketId !== null && (string) ($ticket['id'] ?? '') === $excludeTicketId) {
                continue;
            }
            $count++;
        }
        if ($count >= $limit) {
            throw new InvalidArgumentException('Column WIP limit reached.');
        }
    }

    private function normalizeWipLimit(mixed $value): int
    {
        if (!is_numeric($value)) {
            return 0;
        }
        $limit = (int) $value;
        if ($limit < 1) {
            return 0;
        }

        return min(99, $limit);
    }

    /**
     * @return array{schema: string, replies: list<array{id: string, title: string, body: string}>}|null
     */
    private function readCanned(): ?array
    {
        if (!$this->reader->exists($this->cannedPath)) {
            return null;
        }

        try {
            $data = JsonHelper::decode($this->reader->read($this->cannedPath));
        } catch (\Throwable) {
            return null;
        }

        try {
            return [
                'schema' => self::CANNED_SCHEMA,
                'replies' => $this->normalizeCannedReplies($data['replies'] ?? []),
            ];
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @param array{schema: string, replies: list<array{id: string, title: string, body: string}>} $canned
     * @return array{schema: string, replies: list<array{id: string, title: string, body: string}>}
     */
    private function writeCanned(array $canned): array
    {
        $canonical = [
            'schema' => self::CANNED_SCHEMA,
            'replies' => $this->normalizeCannedReplies($canned['replies']),
        ];
        $this->writer->write(
            $this->cannedPath,
            JsonHelper::encode($canonical, JSON_PRETTY_PRINT),
            false
        );

        return $canonical;
    }

    /**
     * @return list<string>
     */
    private function ticketFiles(): array
    {
        try {
            $files = $this->reader->listFiles($this->ticketDir, '*.json');
        } catch (\Throwable) {
            return [];
        }

        $names = [];
        foreach ($files as $file) {
            if (str_ends_with($file, '.json')) {
                $names[] = $file;
            }
        }

        return $names;
    }

    private function ticketPath(string $id): string
    {
        return $this->ticketDir . '/' . $id . '.json';
    }

    private function nextOrder(string $columnId): int
    {
        $max = -1;
        foreach ($this->listTicketsInScope() as $ticket) {
            if ((string) $ticket['columnId'] === $columnId) {
                $max = max($max, (int) $ticket['order']);
            }
        }

        return $max + 1;
    }

    /**
     * @param mixed $raw
     * @return list<array<string, mixed>>
     */
    private function normalizeColumns(mixed $raw): array
    {
        if (!is_array($raw)) {
            throw new InvalidArgumentException('Columns must be a list.');
        }

        $out = [];
        $seen = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = is_string($row['id'] ?? null) ? trim($row['id']) : '';
            if ($id === '' || !preg_match('/^[a-z][a-z0-9_-]{0,31}$/', $id)) {
                $id = 'col_' . bin2hex(random_bytes(4));
            }
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $label = trim(is_string($row['label'] ?? null) ? $row['label'] : '');
            if ($label === '') {
                throw new InvalidArgumentException('Column label is required.');
            }
            $out[] = [
                'id' => $id,
                'label' => mb_substr($label, 0, self::MAX_COLUMN_LABEL),
                'color' => $this->normalizeColor(is_string($row['color'] ?? null) ? $row['color'] : '#2c7be5'),
                'wipLimit' => $this->normalizeWipLimit($row['wipLimit'] ?? 0),
            ];
            if (count($out) >= self::MAX_COLUMNS) {
                break;
            }
        }

        return $out;
    }

    /**
     * @param mixed $raw
     * @return list<array<string, mixed>>
     */
    private function normalizeLabels(mixed $raw): array
    {
        if ($raw === null) {
            return [];
        }
        if (!is_array($raw)) {
            throw new InvalidArgumentException('Labels must be a list.');
        }

        $out = [];
        $seen = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = is_string($row['id'] ?? null) ? trim($row['id']) : '';
            if ($id === '' || !preg_match('/^[a-z][a-z0-9_-]{0,31}$/', $id)) {
                $id = 'lbl_' . bin2hex(random_bytes(4));
            }
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $name = trim(is_string($row['name'] ?? null) ? $row['name'] : '');
            if ($name === '') {
                continue;
            }
            $out[] = [
                'id' => $id,
                'name' => mb_substr($name, 0, self::MAX_LABEL_NAME),
                'color' => $this->normalizeColor(is_string($row['color'] ?? null) ? $row['color'] : '#748194'),
            ];
            if (count($out) >= self::MAX_LABELS) {
                break;
            }
        }

        return $out;
    }

    /**
     * @param mixed $raw
     * @param array{labels: list<array<string, mixed>>} $board
     * @return list<string>
     */
    private function normalizeTicketLabels(mixed $raw, array $board): array
    {
        $allowed = [];
        foreach ($board['labels'] as $label) {
            $allowed[(string) $label['id']] = true;
        }
        $out = [];
        if (!is_array($raw)) {
            return $out;
        }
        foreach ($raw as $labelId) {
            if (is_string($labelId) && isset($allowed[$labelId]) && !in_array($labelId, $out, true)) {
                $out[] = $labelId;
            }
        }

        return $out;
    }

    /**
     * @param list<string> $agentIds
     */
    private function normalizeAssignee(string $userId, array $agentIds): ?string
    {
        $userId = trim($userId);
        if ($userId === '') {
            return null;
        }
        if (!in_array($userId, $agentIds, true)) {
            throw new InvalidArgumentException('Assignee must be on a Support team.');
        }

        return mb_substr($userId, 0, 64);
    }

    private function normalizeSubject(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new InvalidArgumentException('Subject is required.');
        }

        return mb_substr($value, 0, self::MAX_SUBJECT);
    }

    private function normalizeBody(string $value): string
    {
        return mb_substr(trim($value), 0, self::MAX_BODY);
    }

    private function normalizeName(string $value): string
    {
        return mb_substr(trim($value), 0, self::MAX_NAME);
    }

    private function normalizeEmail(string $value): string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return '';
        }
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false || strlen($value) > self::MAX_EMAIL) {
            throw new InvalidArgumentException('Requester email is invalid.');
        }

        return $value;
    }

    private function normalizeMessageId(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (!preg_match('/^[a-zA-Z0-9._-]{1,' . self::MAX_MESSAGE_ID . '}$/', $value)) {
            throw new InvalidArgumentException('Message id is invalid.');
        }

        return $value;
    }

    private function normalizeColumnRef(string $value): string
    {
        $value = trim($value);

        return $value !== '' ? mb_substr($value, 0, 32) : 'open';
    }

    private function normalizeOrder(mixed $value): int
    {
        $order = is_int($value) ? $value : (is_numeric($value) ? (int) $value : 0);

        return max(0, min(9999, $order));
    }

    private function normalizeDueAt(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return null;
        }
        if (!is_int($value) && !is_numeric($value)) {
            throw new InvalidArgumentException('Due date is invalid.');
        }

        return max(0, (int) $value);
    }

    private function normalizeNoteBody(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new InvalidArgumentException('Note body is required.');
        }

        return mb_substr($value, 0, self::MAX_NOTE_BODY);
    }

    /**
     * @param mixed $raw
     * @return list<array{id: string, body: string, authorUserId: string, createdAt: int}>
     */
    private function normalizeStoredNotes(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = is_string($row['id'] ?? null) ? $row['id'] : '';
            $body = is_string($row['body'] ?? null) ? trim($row['body']) : '';
            $author = is_string($row['authorUserId'] ?? null) ? trim($row['authorUserId']) : '';
            $createdAt = is_int($row['createdAt'] ?? null)
                ? $row['createdAt']
                : (is_numeric($row['createdAt'] ?? null) ? (int) $row['createdAt'] : 0);
            if ($id === '' || !preg_match('/^nte_[a-f0-9]{10}$/', $id) || $body === '' || $author === '') {
                continue;
            }
            $out[] = [
                'id' => $id,
                'body' => mb_substr($body, 0, self::MAX_NOTE_BODY),
                'authorUserId' => mb_substr($author, 0, 64),
                'createdAt' => max(0, $createdAt),
            ];
            if (count($out) >= self::MAX_NOTES) {
                break;
            }
        }

        return $out;
    }

    /**
     * @param mixed $raw
     * @return list<array{id: string, title: string, body: string}>
     */
    private function normalizeCannedReplies(mixed $raw): array
    {
        if (!is_array($raw)) {
            throw new InvalidArgumentException('Canned replies must be a list.');
        }

        $out = [];
        $seen = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = is_string($row['id'] ?? null) ? trim($row['id']) : '';
            if ($id === '' || !preg_match('/^cnd_[a-f0-9]{10}$/', $id)) {
                $id = 'cnd_' . bin2hex(random_bytes(5));
            }
            if (isset($seen[$id])) {
                continue;
            }
            $title = trim(is_string($row['title'] ?? null) ? $row['title'] : '');
            $body = is_string($row['body'] ?? null) ? trim($row['body']) : '';
            if ($title === '' || $body === '') {
                continue;
            }
            $seen[$id] = true;
            $out[] = [
                'id' => $id,
                'title' => mb_substr($title, 0, self::MAX_CANNED_TITLE),
                'body' => mb_substr($body, 0, self::MAX_CANNED_BODY),
            ];
            if (count($out) >= self::MAX_CANNED) {
                break;
            }
        }

        return $out;
    }

    private function normalizeColor(string $value): string
    {
        $value = strtolower(trim($value));
        if (preg_match('/^#[0-9a-f]{6}$/', $value) !== 1) {
            return '#2c7be5';
        }

        return $value;
    }

    private function normalizeTicketId(string $id): string
    {
        $id = trim($id);
        if (preg_match('/^tkt_[a-f0-9]{10}$/', $id) !== 1) {
            throw new InvalidArgumentException('Ticket id is invalid.');
        }

        return $id;
    }
}
