<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Messages\Models;

use JsonSerializable;

class ContactMessage implements JsonSerializable
{
    private string $id;
    private string $name;
    private string $email;
    private string $subject;
    private string $message;
    private string $createdAt;
    private bool $isRead = false;
    private string $ip = 'unknown';

    public function __construct(string $name, string $email, string $message)
    {
        $this->id = uniqid('msg_', true);
        $this->name = $name;
        $this->email = $email;
        $this->message = $message;
        $this->subject = 'General inquiry';
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

    public function getEmail(): string
    {
        return $this->email;
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

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * @param array<int|string, mixed> $entry
 */public static function fromArray(array $entry, string $id): self
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
 * @return array<int|string, mixed>
 */public function jsonSerialize(): array
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
            'ip' => $this->ip,
        ];
    }
}
