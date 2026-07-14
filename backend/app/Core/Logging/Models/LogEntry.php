<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Logging\Models;

use JsonSerializable;

/**
 * Model pre jednu logovaciu položku.
 */
class LogEntry implements JsonSerializable
{
    private string $id;
    private string $timestamp;
    private string $severity;
    private string $category;
    private string $message;
    private ?string $userId = null;
    private ?string $ip = null;
    private ?array $context = null;
    private ?string $file = null;
    private ?int $line = null;

    public function __construct(
        string $severity,
        string $category,
        string $message
    ) {
        if (!LogSeverity::isValid($severity)) {
            throw new \InvalidArgumentException('Neplatná priorita: ' . $severity);
        }

        $this->id = uniqid('log_', true);
        $this->timestamp = date('Y-m-d H:i:s');
        $this->severity = $severity;
        $this->category = $category;
        $this->message = $message;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): self
    {
        $this->ip = $ip;
        return $this;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public function setContext(?array $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(?string $file): self
    {
        $this->file = $file;
        return $this;
    }

    public function getLine(): ?int
    {
        return $this->line;
    }

    public function setLine(?int $line): self
    {
        $this->line = $line;
        return $this;
    }

    public function isInfo(): bool
    {
        return $this->severity === LogSeverity::INFO;
    }

    public function isWarning(): bool
    {
        return $this->severity === LogSeverity::WARNING;
    }

    public function isError(): bool
    {
        return $this->severity === LogSeverity::ERROR;
    }

    public function isCritical(): bool
    {
        return $this->severity === LogSeverity::CRITICAL;
    }

    public function isDebug(): bool
    {
        return $this->severity === LogSeverity::DEBUG;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'timestamp' => $this->timestamp,
            'severity' => $this->severity,
            'category' => $this->category,
            'message' => $this->message,
            'userId' => $this->userId,
            'ip' => $this->ip,
            'context' => $this->context,
            'file' => $this->file,
            'line' => $this->line,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
