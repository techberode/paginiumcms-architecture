<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Messages\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Modules\Messages\Contracts\MessageRepositoryInterface;
use PaginiumCMS\Modules\Messages\Models\ContactMessage;

class MessageRepository implements MessageRepositoryInterface
{
    private const DIRECTORY = 'data/messages';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer
    ) {
    }

    /**
     * @return array<int|string, mixed>
     */
    public function findAll(): array
    {
        $messages = [];

        try {
            $files = $this->reader->listFiles(self::DIRECTORY, '*.json');
        } catch (FlatFileException) {
            return [];
        }

        foreach ($files as $file) {
            $id = pathinfo($file, PATHINFO_FILENAME);
            $message = $this->findById($id);
            if ($message !== null) {
                $messages[] = $message;
            }
        }

        usort($messages, fn (ContactMessage $a, ContactMessage $b) => strcmp($b->getCreatedAt(), $a->getCreatedAt()));

        return $messages;
    }

    /**
     * @return list<ContactMessage>
     */
    public function findByEmail(string $email): array
    {
        $normalized = strtolower(trim($email));
        if ($normalized === '') {
            return [];
        }

        return array_values(array_filter(
            $this->findAll(),
            static fn (ContactMessage $message): bool => strtolower(trim($message->getEmail())) === $normalized
        ));
    }

    public function findById(string $id): ?ContactMessage
    {
        $path = self::DIRECTORY . '/' . $id . '.json';
        if (!$this->reader->exists($path)) {
            return null;
        }

        try {
            $content = $this->reader->read($path);
            $data = json_decode($content, true);

            return is_array($data) ? ContactMessage::fromArray($data, $id) : null;
        } catch (FlatFileException) {
            return null;
        }
    }

    public function save(ContactMessage $message): void
    {
        $json = json_encode($message->jsonSerialize(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new FlatFileException('Failed to serialize contact message');
        }

        $this->writer->write($message->getPath(), $json, true);
    }

    public function update(ContactMessage $message): void
    {
        if ($this->findById($message->getId()) === null) {
            throw new FlatFileException('Message not found');
        }

        $this->save($message);
    }

    public function delete(string $id): void
    {
        $path = self::DIRECTORY . '/' . $id . '.json';
        if (!$this->reader->exists($path)) {
            throw new FlatFileException('Message not found');
        }

        $this->writer->delete($path, true);
    }
}
