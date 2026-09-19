<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Support\LogSanitizer;

/**
 * Public registration types linked to RBAC roles (It.93o-7).
 */
final class RegistrationOptionsStore
{
    public const SCHEMA = 'registration-options@1';

    public const MAX_OPTIONS = 24;

    public const MAX_LABEL = 80;

    public const MAX_MAIL = 4000;

    /** @var list<string> */
    private const FORBIDDEN_ROLES = [
        AuthorizationInterface::ROLE_ADMIN,
        AuthorizationInterface::ROLE_SUPER_ADMIN,
    ];

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private RoleRepository $roles,
        private string $relativePath = 'data/registration-options.json',
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        return $this->read()['options'];
    }

    /**
     * Seed Developer / Publicist / Editor the first time an admin opens the panel.
     *
     * @return list<array<string, mixed>>
     */
    public function seedIfEmpty(): array
    {
        if ($this->reader->exists($this->relativePath)) {
            return $this->list();
        }

        try {
            return $this->save($this->defaults());
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public function publicList(): array
    {
        $out = [];
        foreach ($this->list() as $option) {
            if (($option['enabled'] ?? false) !== true) {
                continue;
            }
            $out[] = [
                'id' => (string) $option['id'],
                'label' => (string) $option['label'],
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $id): ?array
    {
        $id = trim($id);
        foreach ($this->list() as $option) {
            if ((string) $option['id'] === $id) {
                return $option;
            }
        }

        return null;
    }

    /**
     * @param array<int|string, mixed> $options
     * @return list<array<string, mixed>>
     */
    public function save(array $options): array
    {
        if (count($options) > self::MAX_OPTIONS) {
            throw new InvalidArgumentException('Too many registration types.');
        }
        $assignable = $this->roles->assignableIds();
        $normalized = [];
        $seen = [];
        foreach ($options as $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $record = $this->normalizeOption($raw, $assignable);
            if (isset($seen[$record['id']])) {
                throw new InvalidArgumentException('Duplicate registration type id.');
            }
            $seen[$record['id']] = true;
            $normalized[] = $record;
        }

        $this->writer->write(
            $this->relativePath,
            JsonHelper::encode([
                'schema' => self::SCHEMA,
                'options' => $normalized,
                'updatedAt' => time(),
            ], JSON_PRETTY_PRINT),
            false
        );

        return $normalized;
    }

    /**
     * @return array{schema: string, options: list<array<string, mixed>>, updatedAt: int}
     */
    private function read(): array
    {
        if (!$this->reader->exists($this->relativePath)) {
            return ['schema' => self::SCHEMA, 'options' => [], 'updatedAt' => 0];
        }

        try {
            $data = JsonHelper::decode($this->reader->read($this->relativePath));
        } catch (\Throwable) {
            return ['schema' => self::SCHEMA, 'options' => [], 'updatedAt' => 0];
        }

        $options = [];
        $raw = $data['options'] ?? [];
        if (is_array($raw)) {
            foreach ($raw as $item) {
                if (is_array($item) && is_string($item['id'] ?? null)) {
                    $options[] = $item;
                }
            }
        }

        return [
            'schema' => self::SCHEMA,
            'options' => $options,
            'updatedAt' => is_int($data['updatedAt'] ?? null) ? $data['updatedAt'] : 0,
        ];
    }

    /**
     * @param array<string, mixed> $raw
     * @param list<string> $assignable
     * @return array<string, mixed>
     */
    private function normalizeOption(array $raw, array $assignable): array
    {
        $id = strtolower(trim((string) ($raw['id'] ?? '')));
        if ($id === '' || !preg_match('/^regopt_[a-f0-9]{8}$/', $id)) {
            $id = 'regopt_' . bin2hex(random_bytes(4));
        }
        $label = LogSanitizer::value(trim((string) ($raw['label'] ?? '')), self::MAX_LABEL);
        if ($label === '') {
            throw new InvalidArgumentException('Registration type label is required.');
        }
        $roleId = strtoupper(trim((string) ($raw['roleId'] ?? AuthorizationInterface::ROLE_USER)));
        if (in_array($roleId, self::FORBIDDEN_ROLES, true) || !in_array($roleId, $assignable, true)) {
            throw new InvalidArgumentException('Registration type cannot assign this role.');
        }
        $teamId = strtolower(trim((string) ($raw['assignTeamId'] ?? '')));
        if ($teamId !== '' && !preg_match('/^team_[a-f0-9]{10}$/', $teamId)) {
            throw new InvalidArgumentException('Invalid team id on registration type.');
        }

        return [
            'id' => $id,
            'label' => $label,
            'roleId' => $roleId,
            'enabled' => (bool) ($raw['enabled'] ?? true),
            'requireAdminApproval' => (bool) ($raw['requireAdminApproval'] ?? true),
            'assignTeamId' => $teamId,
            'welcomeMailEnabled' => (bool) ($raw['welcomeMailEnabled'] ?? true),
            'welcomeMailSubject' => LogSanitizer::value((string) ($raw['welcomeMailSubject'] ?? ''), 160),
            'welcomeMailBody' => LogSanitizer::value((string) ($raw['welcomeMailBody'] ?? ''), self::MAX_MAIL),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function defaults(): array
    {
        $assignable = $this->roles->assignableIds();
        $editor = in_array(AuthorizationInterface::ROLE_EDITOR, $assignable, true)
            ? AuthorizationInterface::ROLE_EDITOR
            : AuthorizationInterface::ROLE_USER;

        return [
            [
                'id' => 'regopt_' . bin2hex(random_bytes(4)),
                'label' => 'Developer team member',
                'roleId' => AuthorizationInterface::ROLE_USER,
                'enabled' => true,
                'requireAdminApproval' => true,
                'assignTeamId' => '',
                'welcomeMailEnabled' => true,
                'welcomeMailSubject' => 'Your registration was approved',
                'welcomeMailBody' => 'Welcome. An administrator approved your registration. You can sign in now.',
            ],
            [
                'id' => 'regopt_' . bin2hex(random_bytes(4)),
                'label' => 'Publicist',
                'roleId' => AuthorizationInterface::ROLE_USER,
                'enabled' => true,
                'requireAdminApproval' => true,
                'assignTeamId' => '',
                'welcomeMailEnabled' => true,
                'welcomeMailSubject' => 'Your registration was approved',
                'welcomeMailBody' => 'Welcome. An administrator approved your registration. You can sign in now.',
            ],
            [
                'id' => 'regopt_' . bin2hex(random_bytes(4)),
                'label' => 'Editor',
                'roleId' => $editor,
                'enabled' => true,
                'requireAdminApproval' => true,
                'assignTeamId' => '',
                'welcomeMailEnabled' => true,
                'welcomeMailSubject' => 'Your editor account is ready',
                'welcomeMailBody' => 'Welcome. Your editor registration was approved. You can sign in now.',
            ],
        ];
    }
}
