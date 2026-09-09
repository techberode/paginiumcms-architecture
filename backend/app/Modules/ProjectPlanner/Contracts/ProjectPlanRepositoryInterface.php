<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\ProjectPlanner\Contracts;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Exception\InvalidPathException;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Modules\ProjectPlanner\Models\ProjectPlan;

interface ProjectPlanRepositoryInterface
{
    /**
     * @return list<ProjectPlan>
     */
    public function findAll(): array;

    /**
     * @throws InvalidPathException
     */
    public function findById(string $id): ?ProjectPlan;

    public function findDefault(): ?ProjectPlan;

    /**
     * @param array<string, mixed> $payload
     *
     * @throws ValidationException
     * @throws InvalidPathException
     * @throws FlatFileException
     */
    public function create(array $payload): ProjectPlan;

    /**
     * @param array<string, mixed> $payload
     *
     * @throws ValidationException
     * @throws InvalidPathException
     * @throws FlatFileException
     */
    public function update(string $id, array $payload): ProjectPlan;

    /**
     * @throws ValidationException
     * @throws InvalidPathException
     * @throws FlatFileException
     */
    public function save(ProjectPlan $plan): ProjectPlan;

    /**
     * @throws InvalidPathException
     * @throws FlatFileException
     */
    public function delete(string $id): void;

    /**
     * @param array<string, mixed> $payload
     *
     * @throws ValidationException
     * @throws InvalidPathException
     * @throws FlatFileException
     */
    public function addItem(string $planId, array $payload): ProjectPlan;

    /**
     * @param array<string, mixed> $payload
     *
     * @throws ValidationException
     * @throws InvalidPathException
     * @throws FlatFileException
     */
    public function updateItem(string $planId, string $itemId, array $payload): ProjectPlan;

    /**
     * @throws ValidationException
     * @throws InvalidPathException
     * @throws FlatFileException
     */
    public function deleteItem(string $planId, string $itemId): ProjectPlan;
}
