<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Agent\Services;

use PaginiumCMS\Core\Agent\Contracts\LlmProviderInterface;
use PaginiumCMS\Core\Agent\Drivers\NoneLlmDriver;
use PaginiumCMS\Core\Agent\Drivers\OpenAiCompatibleLlmDriver;
use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;

final class AgentLlmProviderRegistry
{
    public function __construct(
        private AgentSettings $settings,
        private ?OutboundUrlGuard $urlGuard = null,
        private ?LlmProviderInterface $override = null,
    ) {
    }

    public function resolve(): LlmProviderInterface
    {
        if ($this->override !== null) {
            return $this->override;
        }

        return match ($this->settings->provider()) {
            'ollama', 'openai_compatible' => new OpenAiCompatibleLlmDriver(
                $this->settings,
                $this->settings->provider(),
                $this->urlGuard
            ),
            default => new NoneLlmDriver(),
        };
    }

    public function withOverride(LlmProviderInterface $provider): self
    {
        return new self($this->settings, $this->urlGuard, $provider);
    }
}
