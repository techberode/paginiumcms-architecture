<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Mail\Services;

interface ImapClientInterface
{
    public function connect(string $host, int $port, string $encryption, string $username, string $password): void;

    public function disconnect(): void;

    /**
     * @return list<array{name: string, spam: bool}>
     */
    public function folders(): array;

    public function createFolder(string $name): void;

    public function deleteFolder(string $name): void;

    /**
     * @return list<array<string, mixed>>
     */
    public function messages(string $folder, int $limit = 40): array;

    /**
     * @return array<string, mixed>
     */
    public function message(string $folder, int $uid): array;

    /**
     * @param list<string> $tags
     */
    public function setTags(string $folder, int $uid, array $tags): void;

    /**
     * @param list<string> $flags Tokens like seen, flagged, or keyword tags.
     */
    public function addFlags(string $folder, int $uid, array $flags): void;

    /**
     * @param list<string> $flags
     */
    public function removeFlags(string $folder, int $uid, array $flags): void;

    public function move(string $folder, int $uid, string $target): void;
}
