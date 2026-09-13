<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use RuntimeException;

/**
 * Parsed and validated S3-compatible media storage settings (Iteration 72).
 */
final class S3MediaStorageConfig
{
    public function __construct(
        public readonly string $bucket,
        public readonly string $region,
        public readonly string $keyId,
        public readonly string $secret,
        public readonly string $endpoint,
        public readonly bool $pathStyle,
        public readonly string $publicBaseUrl,
        public readonly string $visibility,
    ) {
    }

    /**
     * @param array<string, mixed> $mediaSettings
     */
    public static function tryFromSettings(array $mediaSettings, OutboundUrlGuard $guard): ?self
    {
        try {
            return self::fromSettings($mediaSettings, $guard);
        } catch (RuntimeException) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $mediaSettings
     */
    public static function fromSettings(array $mediaSettings, OutboundUrlGuard $guard): self
    {
        $bucket = trim((string) ($mediaSettings['s3Bucket'] ?? ''));
        $region = trim((string) ($mediaSettings['s3Region'] ?? ''));
        $keyId = trim((string) ($mediaSettings['s3KeyId'] ?? ''));
        $secret = trim((string) ($mediaSettings['s3Secret'] ?? ''));
        $endpoint = trim((string) ($mediaSettings['s3Endpoint'] ?? ''));
        $publicBaseUrl = trim((string) ($mediaSettings['s3PublicBaseUrl'] ?? ''));
        $visibility = strtolower(trim((string) ($mediaSettings['s3Visibility'] ?? 'private')));
        $pathStyle = (bool) ($mediaSettings['s3PathStyle'] ?? false);

        if ($bucket === '' || $region === '' || $keyId === '' || $secret === '') {
            throw new RuntimeException('Incomplete S3 media storage configuration');
        }

        if (!in_array($visibility, ['private', 'public'], true)) {
            throw new RuntimeException('Invalid S3 visibility');
        }

        if ($endpoint !== '') {
            self::assertSafePublicUrl($endpoint, $guard);
        }

        if ($publicBaseUrl !== '') {
            self::assertSafePublicUrl($publicBaseUrl, $guard);
        }

        return new self(
            $bucket,
            $region,
            $keyId,
            $secret,
            $endpoint,
            $pathStyle,
            $publicBaseUrl,
            $visibility,
        );
    }

    public function isComplete(): bool
    {
        return $this->bucket !== ''
            && $this->region !== ''
            && $this->keyId !== ''
            && $this->secret !== '';
    }

    /**
     * Redacted summary safe for logs and API probes.
     *
     * @return array<string, mixed>
     */
    public function redactedSummary(): array
    {
        return [
            'bucket' => $this->bucket,
            'region' => $this->region,
            'endpointConfigured' => $this->endpoint !== '',
            'pathStyle' => $this->pathStyle,
            'publicBaseUrlConfigured' => $this->publicBaseUrl !== '',
            'visibility' => $this->visibility,
            'credentialsConfigured' => $this->keyId !== '' && $this->secret !== '',
        ];
    }

    private static function assertSafePublicUrl(string $url, OutboundUrlGuard $guard): void
    {
        $lower = strtolower($url);
        if (str_starts_with($lower, 'javascript:') || str_starts_with($lower, 'data:')) {
            throw new RuntimeException('Blocked URL scheme for S3 media settings');
        }

        $guard->assertAllowed($url);
    }
}
