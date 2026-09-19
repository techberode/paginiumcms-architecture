<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Messages\Models;

use JsonSerializable;

class ContactMessage implements JsonSerializable
{
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    /** @var list<string> */
    public const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_NORMAL,
        self::PRIORITY_HIGH,
        self::PRIORITY_URGENT,
    ];

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DONE = 'done';

    public const SUBJECT_GENERAL = 'Všeobecný dotaz';
    public const SUBJECT_SUPPORT = 'Technická podpora';
    public const SUBJECT_SALES = 'Obchodné informácie';
    public const SUBJECT_PARTNERSHIP = 'Spolupráca';
    public const SUBJECT_REGISTRATION = 'Žiadosť o registráciu';

    /** @var list<string> */
    public const SUBJECT_PRESETS = [
        self::SUBJECT_GENERAL,
        self::SUBJECT_SUPPORT,
        self::SUBJECT_SALES,
        self::SUBJECT_PARTNERSHIP,
        self::SUBJECT_REGISTRATION,
    ];

    private string $id;
    private string $name;
    private string $email;
    private string $phone = '';
    private string $subject;
    private string $message;
    private string $createdAt;
    private bool $isRead = false;
    private bool $isProcessed = false;
    private bool $isArchived = false;
    private string $priority = self::PRIORITY_NORMAL;
    private string $ip = 'unknown';
    private string $staffUserId = '';
    private string $channel = 'contact';
    /** @var list<string> */
    private array $assigneeUserIds = [];
    /** @var list<string> */
    private array $assigneeTeamIds = [];
    private string $claimedBy = '';
    private int $claimedAt = 0;
    private string $handleStatus = self::STATUS_OPEN;
    private string $notifyUserId = '';
    private bool $registrationRequest = false;
    /** @var list<array{id: string, authorType: string, authorUserId: string, authorName: string, body: string, createdAt: string}> */
    private array $thread = [];
    private bool $lastMailed = false;

    public function __construct(string $name, string $email, string $message)
    {
        $this->id = uniqid('msg_', true);
        $this->name = $name;
        $this->email = $email;
        $this->message = $message;
        $this->subject = self::SUBJECT_GENERAL;
        $this->createdAt = date('c');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPath(): string
    {
        return 'data/messages/' . $this->id . '.json';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = trim($phone);

        return $this;
    }

    public function setLastMailed(bool $mailed): self
    {
        $this->lastMailed = $mailed;

        return $this;
    }

    public function wasLastMailed(): bool
    {
        return $this->lastMailed;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function markRead(bool $isRead = true): self
    {
        $this->isRead = $isRead;
        return $this;
    }

    public function isProcessed(): bool
    {
        return $this->isProcessed;
    }

    public function markProcessed(bool $isProcessed = true): self
    {
        $this->isProcessed = $isProcessed;
        if ($isProcessed) {
            $this->handleStatus = self::STATUS_DONE;
        } elseif ($this->handleStatus === self::STATUS_DONE) {
            $this->handleStatus = $this->claimedBy !== '' ? self::STATUS_IN_PROGRESS : self::STATUS_OPEN;
        }

        return $this;
    }

    public function isArchived(): bool
    {
        return $this->isArchived;
    }

    public function markArchived(bool $isArchived = true): self
    {
        $this->isArchived = $isArchived;
        return $this;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): self
    {
        if (!in_array($priority, self::PRIORITIES, true)) {
            $priority = self::PRIORITY_NORMAL;
        }

        $this->priority = $priority;
        return $this;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;
        return $this;
    }

    public function getStaffUserId(): string
    {
        return $this->staffUserId;
    }

    public function setStaffUserId(string $staffUserId): self
    {
        $this->staffUserId = trim($staffUserId);

        return $this;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function setChannel(string $channel): self
    {
        $this->channel = $channel === 'staff-chat' ? 'staff-chat' : 'contact';

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getAssigneeUserIds(): array
    {
        return $this->assigneeUserIds;
    }

    /**
     * @param list<mixed> $ids
     */
    public function setAssigneeUserIds(array $ids): self
    {
        $this->assigneeUserIds = self::normalizeIds($ids);

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getAssigneeTeamIds(): array
    {
        return $this->assigneeTeamIds;
    }

    /**
     * @param list<mixed> $ids
     */
    public function setAssigneeTeamIds(array $ids): self
    {
        $this->assigneeTeamIds = self::normalizeIds($ids);

        return $this;
    }

    public function getClaimedBy(): string
    {
        return $this->claimedBy;
    }

    public function setClaimedBy(string $userId): self
    {
        $this->claimedBy = trim($userId);

        return $this;
    }

    public function getClaimedAt(): int
    {
        return $this->claimedAt;
    }

    public function setClaimedAt(int $claimedAt): self
    {
        $this->claimedAt = max(0, $claimedAt);

        return $this;
    }

    public function getHandleStatus(): string
    {
        return $this->handleStatus;
    }

    public function setHandleStatus(string $status): self
    {
        $this->handleStatus = in_array($status, [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_DONE], true)
            ? $status
            : self::STATUS_OPEN;

        return $this;
    }

    public function getNotifyUserId(): string
    {
        return $this->notifyUserId;
    }

    public function isRegistrationRequest(): bool
    {
        return $this->registrationRequest;
    }

    public function setRegistrationRequest(bool $requested): self
    {
        $this->registrationRequest = $requested;

        return $this;
    }

    public function setNotifyUserId(string $userId): self
    {
        $this->notifyUserId = trim($userId);

        return $this;
    }

    /**
     * @return list<array{id: string, authorType: string, authorUserId: string, authorName: string, body: string, createdAt: string}>
     */
    public function getThread(): array
    {
        return $this->thread;
    }

    /**
     * @param array{id?: string, authorType?: string, authorUserId?: string, authorName?: string, body?: string, createdAt?: string} $reply
     */
    public function addReply(array $reply): self
    {
        $body = trim((string) ($reply['body'] ?? ''));
        if ($body === '' || count($this->thread) >= 80) {
            return $this;
        }

        $type = ($reply['authorType'] ?? '') === 'staff' ? 'staff' : 'visitor';
        $id = trim((string) ($reply['id'] ?? ''));
        $createdAt = trim((string) ($reply['createdAt'] ?? ''));
        $this->thread[] = [
            'id' => $id !== '' ? $id : uniqid('rep_', true),
            'authorType' => $type,
            'authorUserId' => trim((string) ($reply['authorUserId'] ?? '')),
            'authorName' => trim((string) ($reply['authorName'] ?? '')),
            'body' => mb_substr($body, 0, 5000),
            'createdAt' => $createdAt !== '' ? $createdAt : date('c'),
        ];

        return $this;
    }

    /**
     * @param list<mixed> $ids
     * @return list<string>
     */
    public static function normalizeIds(array $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            if (!is_string($id)) {
                continue;
            }
            $id = trim($id);
            if ($id !== '' && !in_array($id, $out, true)) {
                $out[] = $id;
            }
        }

        return $out;
    }

    public static function priorityWeight(string $priority): int
    {
        return match ($priority) {
            self::PRIORITY_URGENT => 4,
            self::PRIORITY_HIGH => 3,
            self::PRIORITY_LOW => 1,
            default => 2,
        };
    }

    /**
     * @param array<int|string, mixed> $entry
     */
    public static function fromArray(array $entry, string $id): self
    {
        $message = new self(
            (string) ($entry['name'] ?? ''),
            (string) ($entry['email'] ?? ''),
            (string) ($entry['message'] ?? '')
        );

        $reflection = new \ReflectionClass($message);
        $idProp = $reflection->getProperty('id');
        $idProp->setValue($message, $id);

        if (!empty($entry['phone'])) {
            $message->setPhone((string) $entry['phone']);
        }
        if (!empty($entry['subject'])) {
            $message->setSubject((string) $entry['subject']);
        }
        if (array_key_exists('isRead', $entry)) {
            $message->markRead((bool) $entry['isRead']);
        }
        if (array_key_exists('isProcessed', $entry)) {
            $message->markProcessed((bool) $entry['isProcessed']);
        }
        if (array_key_exists('isArchived', $entry)) {
            $message->markArchived((bool) $entry['isArchived']);
        }
        if (!empty($entry['priority'])) {
            $message->setPriority((string) $entry['priority']);
        }
        if (!empty($entry['createdAt'])) {
            $createdProp = $reflection->getProperty('createdAt');
            $createdProp->setValue($message, (string) $entry['createdAt']);
        }
        if (!empty($entry['ip'])) {
            $message->setIp((string) $entry['ip']);
        }
        if (!empty($entry['staffUserId'])) {
            $message->setStaffUserId((string) $entry['staffUserId']);
        }
        if (!empty($entry['channel'])) {
            $message->setChannel((string) $entry['channel']);
        }
        $users = $entry['assigneeUserIds'] ?? [];
        $teams = $entry['assigneeTeamIds'] ?? [];
        $message->setAssigneeUserIds(is_array($users) ? array_values($users) : []);
        $message->setAssigneeTeamIds(is_array($teams) ? array_values($teams) : []);
        if (!empty($entry['claimedBy'])) {
            $message->setClaimedBy((string) $entry['claimedBy']);
        }
        if (!empty($entry['claimedAt'])) {
            $message->setClaimedAt((int) $entry['claimedAt']);
        }
        if (!empty($entry['handleStatus'])) {
            $message->setHandleStatus((string) $entry['handleStatus']);
        }
        if (!empty($entry['notifyUserId'])) {
            $message->setNotifyUserId((string) $entry['notifyUserId']);
        }
        if (array_key_exists('registrationRequest', $entry)) {
            $message->setRegistrationRequest((bool) $entry['registrationRequest']);
        }
        $thread = $entry['thread'] ?? [];
        if (is_array($thread)) {
            foreach ($thread as $row) {
                if (is_array($row)) {
                    $message->addReply($row);
                }
            }
        }

        return $message;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'path' => $this->getPath(),
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'subject' => $this->subject,
            'message' => $this->message,
            'createdAt' => $this->createdAt,
            'isRead' => $this->isRead,
            'isProcessed' => $this->isProcessed,
            'isArchived' => $this->isArchived,
            'priority' => $this->priority,
            'ip' => $this->ip,
            'staffUserId' => $this->staffUserId,
            'channel' => $this->channel,
            'assigneeUserIds' => $this->assigneeUserIds,
            'assigneeTeamIds' => $this->assigneeTeamIds,
            'claimedBy' => $this->claimedBy,
            'claimedAt' => $this->claimedAt,
            'handleStatus' => $this->handleStatus,
            'notifyUserId' => $this->notifyUserId,
            'registrationRequest' => $this->registrationRequest,
            'thread' => $this->thread,
        ];
    }
}
