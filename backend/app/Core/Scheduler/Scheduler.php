<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler;

class Scheduler
{
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

    public function getJobs(): array
    {
        return $this->jobs;
    }
}
