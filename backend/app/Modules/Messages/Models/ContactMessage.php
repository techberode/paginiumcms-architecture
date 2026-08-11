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

    public const SUBJECT_GENERAL = 'Všeobecný dotaz';
    public const SUBJECT_SUPPORT = 'Technická podpora';
    public const SUBJECT_SALES = 'Obchodné informácie';
    public const SUBJECT_PARTNERSHIP = 'Spolupráca';

    /** @var list<string> */
    public const SUBJECT_PRESETS = [
        self::SUBJECT_GENERAL,
        self::SUBJECT_SUPPORT,
        self::SUBJECT_SALES,
        self::SUBJECT_PARTNERSHIP,
    ];

    private string $id;
    private string $name;
    private string $email;
    private string $subject;
    private string $message;
    private string $createdAt;
    private bool $isRead = false;
    private bool $isProcessed = false;
    private bool $isArchived = false;
    private string $priority = self::PRIORITY_NORMAL;
    private string $ip = 'unknown';

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
            'subject' => $this->subject,
            'message' => $this->message,
            'createdAt' => $this->createdAt,
            'isRead' => $this->isRead,
            'isProcessed' => $this->isProcessed,
            'isArchived' => $this->isArchived,
            'priority' => $this->priority,
            'ip' => $this->ip,
        ];
    }
}
