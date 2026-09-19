<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Agent\Drivers;

use PaginiumCMS\Core\Agent\Contracts\LlmProviderInterface;
use PaginiumCMS\Core\Agent\Exception\AgentException;
use PaginiumCMS\Core\Agent\Services\AgentSettings;
use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * OpenAI-compatible /v1/chat/completions (Ollama and cloud gateways).
 */
final class OpenAiCompatibleLlmDriver implements LlmProviderInterface
{
    public function __construct(
        private AgentSettings $settings,
        private string $id,
        private ?OutboundUrlGuard $urlGuard = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function complete(array $messages, array $tools, int $maxTokens): array
    {
        $payload = [
            'model' => $this->settings->model() !== '' ? $this->settings->model() : 'llama3.2',
            'messages' => $messages,
            'max_tokens' => max(16, min($maxTokens, $this->settings->maxTokensPerRun())),
            'temperature' => 0.2,
        ];
        if ($tools !== []) {
            $payload['tools'] = array_map(static function (array $tool): array {
                return [
                    'type' => 'function',
                    'function' => [
                        'name' => $tool['name'],
                        'description' => $tool['description'],
                        'parameters' => $tool['parameters'],
                    ],
                ];
            }, $tools);
            $payload['tool_choice'] = 'auto';
        }

        $decoded = $this->postJson('/v1/chat/completions', $payload);
        $choice = is_array($decoded['choices'][0] ?? null) ? $decoded['choices'][0] : [];
        $message = is_array($choice['message'] ?? null) ? $choice['message'] : [];
        $usage = is_array($decoded['usage'] ?? null) ? $decoded['usage'] : [];
        $tokens = (int) ($usage['total_tokens'] ?? 0);

        $toolCalls = is_array($message['tool_calls'] ?? null) ? $message['tool_calls'] : [];
        $first = is_array($toolCalls[0] ?? null) ? $toolCalls[0] : null;
        if (is_array($first)) {
            $fn = is_array($first['function'] ?? null) ? $first['function'] : [];
            $name = trim((string) ($fn['name'] ?? ''));
            $rawArgs = (string) ($fn['arguments'] ?? '{}');
            try {
                $arguments = $this->stringKeyed(JsonHelper::decode($rawArgs));
            } catch (\Throwable) {
                $arguments = [];
            }

            return [
                'type' => 'tool_call',
                'name' => $name,
                'arguments' => $arguments,
                'tokens' => $tokens,
            ];
        }

        return [
            'type' => 'message',
            'content' => trim((string) ($message['content'] ?? '')),
            'tokens' => $tokens,
        ];
    }

    public function health(): array
    {
        try {
            $this->postJson('/v1/models', null, 'GET');

            return ['ok' => true];
        } catch (AgentException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @param array<string, mixed>|null $body
     * @return array<string, mixed>
     */
    private function postJson(string $path, ?array $body, string $method = 'POST'): array
    {
        $base = rtrim($this->settings->baseUrl(), '/');
        if ($base === '') {
            throw new AgentException('Agent provider URL is missing', 422, 'INVALID');
        }
        $url = $base . $path;
        try {
            ($this->urlGuard ?? OutboundUrlGuard::fromEnv())->assertAllowed($url);
        } catch (RuntimeException) {
            throw new AgentException('Agent provider URL is not allowed', 422, 'SSRF_BLOCKED');
        }

        if ($method !== 'GET' && $method !== 'POST') {
            throw new AgentException('Unsupported HTTP method', 422, 'INVALID');
        }

        $handle = curl_init($url);
        if ($handle === false) {
            throw new AgentException('Agent provider request failed', 503, 'PROVIDER_UNAVAILABLE');
        }

        $headers = [
            'Accept: application/json',
            'User-Agent: PaginiumCMS-Agent',
        ];
        $apiKey = $this->settings->apiKey();
        if ($apiKey !== '') {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->settings->timeoutSeconds(),
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_HTTPHEADER] = $headers;
            $options[CURLOPT_POSTFIELDS] = JsonHelper::encode($body);
        }

        curl_setopt_array($handle, $options);
        $raw = curl_exec($handle);
        $http = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if (!is_string($raw)) {
            throw new AgentException('Agent provider unavailable', 503, 'PROVIDER_UNAVAILABLE');
        }
        if ($http >= 400) {
            throw new AgentException('Agent provider rejected the request', 503, 'PROVIDER_UNAVAILABLE');
        }

        try {
            return $this->stringKeyed(JsonHelper::decode($raw));
        } catch (\Throwable) {
            throw new AgentException('Agent provider returned invalid JSON', 503, 'PROVIDER_UNAVAILABLE');
        }
    }

    /**
     * @param array<int|string, mixed> $decoded
     * @return array<string, mixed>
     */
    private function stringKeyed(array $decoded): array
    {
        $out = [];
        foreach ($decoded as $key => $value) {
            if (is_string($key)) {
                $out[$key] = $value;
            }
        }

        return $out;
    }
}
