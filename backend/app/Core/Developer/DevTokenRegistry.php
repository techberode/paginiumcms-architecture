<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Developer;

/**
 * Registr tokenov pre Developer Mode (hash-only, gitignored).
 *
 * Súbor: storage/dev/registered_tokens.json
 * Do repozitára (privátny GitHub) môžete commitnúť len príklad
 * registered_tokens.example.json – produkčné hash-e nikdy do gitu.
 */
class DevTokenRegistry
{
    private string $registryPath;

    public function __construct(?string $registryPath = null)
    {
        $this->registryPath = $registryPath ?? __DIR__ . '/../../../storage/dev/registered_tokens.json';
        $dir = dirname($this->registryPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (!file_exists($this->registryPath)) {
            file_put_contents($this->registryPath, json_encode(['tokens' => []], JSON_PRETTY_PRINT));
        }
    }

    public function registerFromToken(DevTokenGenerator $generator, string $token): void
    {
        $check = $generator->verifyStructure($token);
        if (!$check['valid']) {
            throw new \InvalidArgumentException($check['reason'] ?? 'Neplatný token');
        }

        $payload = $check['payload'] ?? [];
        $this->register([
            'hash' => hash('sha256', $token),
            'label' => $payload['label'] ?? 'developer',
            'expires_at' => (int) ($payload['exp'] ?? time() + 86400),
            'single_use' => $payload['single'] ?? true,
        ]);
    }

    /**
     * @param array{hash: string, label: string, expires_at: int, single_use?: bool} $entry
     */
    public function register(array $entry): void
    {
        $data = $this->load();
        $data['tokens'][] = [
            'hash' => $entry['hash'],
            'label' => $entry['label'],
            'expires_at' => $entry['expires_at'],
            'single_use' => $entry['single_use'] ?? true,
            'registered_at' => date('c'),
            'used_at' => null,
            'revoked' => false,
        ];
        $this->save($data);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByHash(string $hash): ?array
    {
        foreach ($this->load()['tokens'] as $token) {
            if (($token['hash'] ?? '') === $hash) {
                return $token;
            }
        }

        return null;
    }

    public function markUsed(string $hash): void
    {
        $data = $this->load();
        foreach ($data['tokens'] as &$token) {
            if ($token['hash'] === $hash) {
                $token['used_at'] = date('c');
                break;
            }
        }
        unset($token);
        $this->save($data);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listRegistered(): array
    {
        return $this->load()['tokens'];
    }

    /**
     * @return array{tokens: array<int, array<string, mixed>>}
     */
    private function load(): array
    {
        $raw = file_get_contents($this->registryPath);
        $data = json_decode($raw ?: '{}', true);

        return is_array($data) ? $data + ['tokens' => []] : ['tokens' => []];
    }

    /**
     * @param array{tokens: array<int, array<string, mixed>>} $data
     */
    private function save(array $data): void
    {
        file_put_contents(
            $this->registryPath,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}
