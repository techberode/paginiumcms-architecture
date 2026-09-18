<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\Capabilities;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use Closure;

/**
 * Builds scoped plugin SDKs and invokes hook handlers with runtime context (It.89b).
 */
final class PluginCapabilityBroker
{
    public function __construct(
        private ContentRepositoryInterface $content,
        private MediaRepositoryInterface $media,
        private ?PluginCapabilityAuditor $auditor = null,
    ) {
    }

    /**
     * @param list<string> $capabilities
     */
    public function context(string $pluginId, array $capabilities, ?string $actorUserId = null): PluginRuntimeContext
    {
        $caps = $this->normalizeCapabilities($capabilities);

        return new PluginRuntimeContext(
            $pluginId,
            $caps,
            new PluginContentGateway($this->content, $pluginId, $caps, $actorUserId, $this->auditor),
            new PluginMediaGateway($this->media, $pluginId, $caps, $this->auditor),
            $actorUserId,
        );
    }

    /**
     * @param list<string> $capabilities
     * @param array<string, mixed> $hookContext
     */
    public function invoke(callable $handler, array $hookContext, string $pluginId, array $capabilities): mixed
    {
        $actor = is_string($hookContext['userId'] ?? null) ? $hookContext['userId'] : null;
        $runtime = $this->context($pluginId, $capabilities, $actor);

        if ($this->acceptsRuntime($handler)) {
            return $handler($hookContext, $runtime);
        }

        return $handler($hookContext);
    }

    /**
     * @param list<mixed> $capabilities
     * @return list<string>
     */
    private function normalizeCapabilities(array $capabilities): array
    {
        $out = [];
        foreach ($capabilities as $item) {
            if (!is_string($item)) {
                continue;
            }
            $capability = trim($item);
            if ($capability === '' || in_array($capability, $out, true)) {
                continue;
            }
            $out[] = $capability;
        }

        return $out;
    }

    private function acceptsRuntime(callable $handler): bool
    {
        try {
            if (is_string($handler) && str_contains($handler, '::')) {
                [$class, $method] = explode('::', $handler, 2);
                $ref = new ReflectionMethod($class, $method);
            } else {
                $ref = new ReflectionFunction(Closure::fromCallable($handler));
            }
        } catch (ReflectionException) {
            return false;
        }

        return $ref->getNumberOfParameters() >= 2;
    }
}
