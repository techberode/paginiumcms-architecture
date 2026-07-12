<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Audit\Models;

use JsonSerializable;

/**
 * Model pre jednu položku auditu.
 */
class AuditIssue implements JsonSerializable
{
    private string $severity;
    private string $category;
    private string $title;
    private string $description;
    private ?string $recommendation = null;
    private ?string $file = null;
    private ?int $line = null;
    private ?array $context = null;

    public function __construct(
        string $severity,
        string $category,
        string $title,
        string $description
    ) {
        if (!AuditSeverity::isValid($severity)) {
            throw new \InvalidArgumentException('Neplatná závažnosť: ' . $severity);
        }

        $this->severity = $severity;
        $this->category = $category;
        $this->title = $title;
        $this->description = $description;
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getRecommendation(): ?string
    {
        return $this->recommendation;
    }

    public function setRecommendation(string $recommendation): self
    {
        $this->recommendation = $recommendation;
        return $this;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(string $file): self
    {
        $this->file = $file;
        return $this;
    }

    public function getLine(): ?int
    {
        return $this->line;
    }

    public function setLine(int $line): self
    {
        $this->line = $line;
        return $this;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function getSeverityLevel(): int
    {
        return AuditSeverity::getLevel($this->severity);
    }

    public function isCritical(): bool
    {
        return $this->severity === AuditSeverity::CRITICAL;
    }

    public function isError(): bool
    {
        return $this->severity === AuditSeverity::ERROR;
    }

    public function isWarning(): bool
    {
        return $this->severity === AuditSeverity::WARNING;
    }

    public function isInfo(): bool
    {
        return $this->severity === AuditSeverity::INFO;
    }

    public function jsonSerialize(): array
    {
        return [
            'severity' => $this->severity,
            'category' => $this->category,
            'title' => $this->title,
            'description' => $this->description,
            'recommendation' => $this->recommendation,
            'file' => $this->file,
            'line' => $this->line,
            'context' => $this->context,
        ];
    }
}
