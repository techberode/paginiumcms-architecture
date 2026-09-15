<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Support\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Support Kanban board + tickets (It.93l).
 * Board: data/support-board.json (`support-board@1`).
 * Cards: data/support-tickets/{id}.json (`support-ticket@1`).
 */
final class SupportKanbanRepository
{
    public const BOARD_SCHEMA = 'support-board@1';
    public const TICKET_SCHEMA = 'support-ticket@1';

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

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private string $ticketDir = 'data/support-tickets',
        private string $boardPath = 'data/support-board.json',
    ) {
    }

    /**
     * @return array{schema: string, columns: list<array<string, mixed>>, labels: list<array<string, mixed>>}
     */
    public function getBoard(): array
    {
        $board = $this->readBoard();
        if ($board !== null) {
            return $board;
        }

        return $this->writeBoard($this->defaultBoard());
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{schema: string, columns: list<array<string, mixed>>, labels: list<array<string, mixed>>}
     */
    public function saveBoard(array $payload): array
    {
        $current = $this->getBoard();
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

        $board = $this->writeBoard([
            'schema' => self::BOARD_SCHEMA,
            'columns' => $columns,
            'labels' => $labels,
        ]);

        foreach ($this->listTickets() as $ticket) {
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
                $this->updateTicket((string) $ticket['id'], $patch);
            }
        }

        return $board;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listTickets(): array
    {
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
    public function getTicket(string $id): ?array
    {
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
    public function createTicket(array $payload, array $agentIds): array
    {
        if (count($this->listTickets()) >= self::MAX_TICKETS) {
            throw new InvalidArgumentException('Too many tickets.');
        }

        $board = $this->getBoard();
        $columnIds = [];
        foreach ($board['columns'] as $column) {
            $columnIds[] = (string) $column['id'];
        }
        $columnId = is_string($payload['columnId'] ?? null) ? $payload['columnId'] : '';
        if ($columnId === '' || !in_array($columnId, $columnIds, true)) {
            $columnId = $columnIds[0];
        }

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
            'createdAt' => $now,
            'updatedAt' => $now,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $agentIds
     * @return array<string, mixed>
     */
    public function updateTicket(string $id, array $payload, array $agentIds = []): array
    {
        $id = $this->normalizeTicketId($id);
        $existing = $this->readTicket($id);
        if ($existing === null) {
            throw new InvalidArgumentException('Ticket not found');
        }

        $board = $this->getBoard();
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
            $existing['columnId'] = $columnId;
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

    public function deleteTicket(string $id): void
    {
        $id = $this->normalizeTicketId($id);
        $relativePath = $this->ticketPath($id);
        if (!$this->reader->exists($relativePath)) {
            throw new InvalidArgumentException('Ticket not found');
        }

        $this->writer->delete($relativePath, false);
    }

    public function clearAssignee(string $userId): void
    {
        $userId = trim($userId);
        if ($userId === '') {
            return;
        }

        foreach ($this->listTickets() as $ticket) {
            if ((string) ($ticket['assigneeUserId'] ?? '') === $userId) {
                $this->updateTicket((string) $ticket['id'], ['assigneeUserId' => '']);
            }
        }
    }

    /**
     * @return array{schema: string, columns: list<array<string, mixed>>, labels: list<array<string, mixed>>}
     */
    private function defaultBoard(): array
    {
        return [
            'schema' => self::BOARD_SCHEMA,
            'columns' => [
                ['id' => 'open', 'label' => 'Open', 'color' => '#2c7be5'],
                ['id' => 'pending', 'label' => 'Pending', 'color' => '#f59e0b'],
                ['id' => 'closed', 'label' => 'Closed', 'color' => '#10b981'],
            ],
            'labels' => [],
        ];
    }

    /**
     * @return array{schema: string, columns: list<array<string, mixed>>, labels: list<array<string, mixed>>}|null
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
            ];
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @param array{schema: string, columns: list<array<string, mixed>>, labels: list<array<string, mixed>>} $board
     * @return array{schema: string, columns: list<array<string, mixed>>, labels: list<array<string, mixed>>}
     */
    private function writeBoard(array $board): array
    {
        $canonical = [
            'schema' => self::BOARD_SCHEMA,
            'columns' => $this->normalizeColumns($board['columns']),
            'labels' => $this->normalizeLabels($board['labels']),
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
            'createdAt' => is_int($record['createdAt'] ?? null) ? $record['createdAt'] : time(),
            'updatedAt' => is_int($record['updatedAt'] ?? null) ? $record['updatedAt'] : time(),
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
            'createdAt' => is_int($data['createdAt'] ?? null) ? $data['createdAt'] : 0,
            'updatedAt' => is_int($data['updatedAt'] ?? null) ? $data['updatedAt'] : 0,
        ];
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
        foreach ($this->listTickets() as $ticket) {
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
