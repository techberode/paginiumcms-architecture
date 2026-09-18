<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\Capabilities;

/**
 * Per-invocation SDK handed to plugin hook handlers (It.89b).
 *
 * Plugins must not receive the DI container or FlatFileStorage.
 */
final class PluginRuntimeContext
{
    /**
     * @param list<string> $capabilities
     */
    public function __construct(
        private string $pluginId,
        private array $capabilities,
        private PluginContentGateway $content,
        private PluginMediaGateway $media,
        private ?string $actorUserId,
    ) {
    }

    public function pluginId(): string
    {
        return $this->pluginId;
    }

    /**
     * @return list<string>
     */
    public function capabilities(): array
    {
        return $this->capabilities;
    }

    public function actorUserId(): ?string
    {
        return $this->actorUserId;
    }

    public function can(string $capability): bool
    {
        return in_array($capability, $this->capabilities, true);
    }

    public function content(): PluginContentGateway
    {
        return $this->content;
    }

    public function media(): PluginMediaGateway
    {
        return $this->media;
    }
}
