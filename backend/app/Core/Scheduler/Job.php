<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler;

class Job
{
    private string $name;
    private $callback;
    private string $expression;
    private bool $isRunning = false;

    public function __construct(string $name, callable $callback, string $expression = '* * * * *')
    {
        $this->name = $name;
        $this->callback = $callback;
        $this->expression = $expression;
    }

    public function getName(): string { return $this->name; }
    public function isDue(): bool { return $this->checkCronExpression($this->expression); }

    public function execute(): void
    {
        if ($this->isRunning) return;
        $this->isRunning = true;
        try { call_user_func($this->callback); } finally { $this->isRunning = false; }
    }

    private function checkCronExpression(string $expression): bool
    {
        $parts = explode(' ', $expression);
        if (count($parts) !== 5) return false;
        $now = time();
        $date = getdate($now);
        $fields = ['minute' => $date['minutes'], 'hour' => $date['hours'], 'day' => $date['mday'], 'month' => $date['mon'], 'weekday' => $date['wday']];
        $fieldNames = ['minute', 'hour', 'day', 'month', 'weekday'];
        foreach ($parts as $i => $part) {
            if (!$this->checkField($part, $fields[$fieldNames[$i]])) return false;
        }
        return true;
    }

    private function checkField(string $expression, int $value): bool
    {
        if ($expression === '*') return true;
        if (strpos($expression, '*/') === 0) { $step = (int)substr($expression, 2); return $value % $step === 0; }
        if (strpos($expression, '-') !== false) { [$from, $to] = explode('-', $expression); return $value >= (int)$from && $value <= (int)$to; }
        if (strpos($expression, ',') !== false) { $values = explode(',', $expression); return in_array($value, array_map('intval', $values)); }
        return (int)$expression === $value;
    }
}
