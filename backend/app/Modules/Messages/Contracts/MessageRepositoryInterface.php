<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Messages\Contracts;

use PaginiumCMS\Modules\Messages\Models\ContactMessage;

interface MessageRepositoryInterface
{
    /**
     * @return list<ContactMessage>
     */
    public function findAll(): array;

    public function findById(string $id): ?ContactMessage;

    public function save(ContactMessage $message): void;

    public function update(ContactMessage $message): void;

    public function delete(string $id): void;
}
