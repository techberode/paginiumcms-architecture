<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler;

class Scheduler
{
    /** @var array<int|string, mixed> */
    private array $jobs = [];

    public function addJob(Job $job): void
    {
        $this->jobs[] = $job;
    }

    public function run(): void
    {
        foreach ($this->jobs as $job) {
            if ($job->isDue()) {
                $job->execute();
            }
        }
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getJobs(): array
    {
        return $this->jobs;
    }
}
