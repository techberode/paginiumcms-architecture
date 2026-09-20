<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Playground;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PaginiumCMS\Modules\Playground\PlaygroundPackRegistry;
use PaginiumCMS\Modules\Playground\PlaygroundSettings;
use PHPUnit\Framework\TestCase;

final class PlaygroundPackRegistryTest extends TestCase
{
    public function testListsBundledStarterPack(): void
    {
        $registry = $this->registry(['paginium-starter']);
        $packs = $registry->list(false);
        $ids = array_map(static fn (array $pack): string => (string) $pack['packId'], $packs);

        $this->assertContains('paginium-starter', $ids);
        $starter = $this->packById($packs, 'paginium-starter');
        $this->assertTrue($starter['enabled']);
        $this->assertSame('bundled', $starter['source']['type'] ?? '');
    }

    public function testIncludesFilesOnlyWhenPackEnabled(): void
    {
        $enabled = $this->registry(['paginium-starter'])->list(true);
        $starter = $this->packById($enabled, 'paginium-starter');
        $this->assertIsArray($starter['files']);
        $this->assertArrayHasKey('/App.tsx', $starter['files']);
        $this->assertArrayHasKey('/StatCard.tsx', $starter['files']);

        $disabled = $this->registry([])->list(true);
        $hidden = $this->packById($disabled, 'paginium-starter');
        $this->assertFalse($hidden['enabled']);
        $this->assertArrayNotHasKey('files', $hidden);
    }

    public function testRejectsPathTraversalOnAssets(): void
    {
        $registry = $this->registry(['paginium-starter']);
        $this->assertNull($registry->readAsset('paginium-starter', '../SettingsSchema.php'));
        $this->assertNull($registry->readAsset('paginium-starter', 'manifest.json'));
        $file = $registry->readAsset('paginium-starter', 'StatCard.tsx');
        $this->assertIsArray($file);
        $this->assertStringContainsString('StatCard', $file['content']);
    }

    /**
     * @param list<string> $enabledPacks
     */
    private function registry(array $enabledPacks): PlaygroundPackRegistry
    {
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $settingsRepo->method('get')->willReturnCallback(
            static function (string $key, mixed $default = null) use ($enabledPacks): mixed {
                return match ($key) {
                    'playground.enabled' => true,
                    'playground.template' => 'react-ts',
                    'playground.enabledPacks' => implode(',', $enabledPacks),
                    default => $default,
                };
            }
        );
        $settings = new PlaygroundSettings($settingsRepo, new DemoMode());

        return new PlaygroundPackRegistry(
            dirname(__DIR__, 3) . '/app/Modules/Playground/Resources/packs',
            sys_get_temp_dir() . '/paginium-missing-playground-packs.json',
            $settings
        );
    }

    /**
     * @param list<array<string, mixed>> $packs
     * @return array<string, mixed>
     */
    private function packById(array $packs, string $id): array
    {
        foreach ($packs as $pack) {
            if (($pack['packId'] ?? '') === $id) {
                return $pack;
            }
        }

        self::fail('pack not found: ' . $id);
    }
}
