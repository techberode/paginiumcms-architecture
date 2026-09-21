<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Playground;

use PaginiumCMS\Core\CodeEditor\Services\SyntaxChecker;
use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use PaginiumCMS\Core\CodePolicy\Services\CodePolicyEngine;
use PaginiumCMS\Core\CodePolicy\Services\SecurityScanner;
use PaginiumCMS\Core\CodePolicy\Services\UntrustedPolicyScanner;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Security\Services\ZipEntryGuard;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PaginiumCMS\Modules\Playground\Contracts\PlaygroundArchiveDownloaderInterface;
use PaginiumCMS\Modules\Playground\PlaygroundPackGitImporter;
use PaginiumCMS\Modules\Playground\PlaygroundPackRegistry;
use PaginiumCMS\Modules\Playground\PlaygroundSettings;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Tests\Support\StorageTestHelper;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class PlaygroundPackGitImporterTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir() . '/pag_playground_git_' . uniqid('', true);
        mkdir($this->baseDir . '/data/playground-packs', 0777, true);
        putenv('DEMO_MODE=false');
        $_ENV['DEMO_MODE'] = 'false';
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testImportsAllowListedPackAndRegistersIt(): void
    {
        $bytes = $this->zipBytes([
            'repo-main/manifest.json' => JsonHelper::encode([
                'packId' => 'acme-widgets',
                'manifestVersion' => 1,
                'title' => 'Acme widgets',
                'modules' => [['id' => 'stat', 'sandpackEntry' => 'Stat.tsx', 'capabilities' => ['admin-ui:widget']]],
            ]),
            'repo-main/Stat.tsx' => 'export function Stat(): null { return null; }',
        ]);
        $importer = $this->importer($bytes);
        $result = $importer->importZipBytes($bytes, 'https://github.com/acme/widgets', 'v1');

        $this->assertSame('acme-widgets', $result['packId']);
        $this->assertTrue($result['enabled']);
        $this->assertFileExists($this->baseDir . '/data/playground-packs/acme-widgets/Stat.tsx');
        $registry = JsonHelper::decode((string) file_get_contents($this->baseDir . '/data/playground-packs.json'));
        $this->assertSame('acme-widgets', $registry['packs'][0]['packId'] ?? null);
        $this->assertSame('git', $registry['packs'][0]['source']['type'] ?? null);
    }

    public function testRejectsZipSlip(): void
    {
        $bytes = $this->zipBytes([
            '../../etc/passwd' => 'pwned',
        ]);
        $this->expectException(\RuntimeException::class);
        $this->importer($bytes)->importZipBytes($bytes, 'https://github.com/acme/widgets', 'main');
    }

    public function testRejectsPhpInPack(): void
    {
        $bytes = $this->zipBytes([
            'repo-main/manifest.json' => JsonHelper::encode([
                'packId' => 'evil-pack',
                'manifestVersion' => 1,
                'title' => 'Evil',
            ]),
            'repo-main/hack.php' => '<?php eval($_GET["x"]);',
            'repo-main/App.tsx' => 'export default function App() { return null; }',
        ]);
        $this->expectException(\RuntimeException::class);
        $this->importer($bytes)->importZipBytes($bytes, 'https://github.com/acme/widgets', 'main');
    }

    public function testScanBlocksEvalJavascript(): void
    {
        $bytes = $this->zipBytes([
            'repo-main/manifest.json' => JsonHelper::encode([
                'packId' => 'eval-pack',
                'manifestVersion' => 1,
                'title' => 'Eval',
            ]),
            'repo-main/evil.js' => 'eval("alert(1)");',
        ]);
        $this->expectException(CodePolicyViolationException::class);
        $this->importer($bytes)->importZipBytes($bytes, 'https://github.com/acme/widgets', 'main');
    }

    public function testRejectsArchiveEntryThatExpandsBeyondFileLimit(): void
    {
        $bytes = $this->zipBytes([
            'repo-main/manifest.json' => JsonHelper::encode([
                'packId' => 'oversized-pack',
                'manifestVersion' => 1,
            ]),
            'repo-main/App.tsx' => str_repeat('x', PlaygroundPackRegistry::MAX_FILE_BYTES + 1),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('entry is too large');
        $this->importer($bytes)->importZipBytes($bytes, 'https://github.com/acme/widgets', 'main');
    }

    /**
     * @param array<string, string> $files
     */
    private function zipBytes(array $files): string
    {
        $path = $this->baseDir . '/fixture.zip';
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        return (string) file_get_contents($path);
    }

    private function importer(string $bytes): PlaygroundPackGitImporter
    {
        $validator = new FileValidator($this->baseDir);
        $writer = new FileWriter($validator);
        $settingsRepo = new SettingsRepository(
            $writer,
            StorageTestHelper::localStorage($this->baseDir),
            new Validator(),
            'data/settings.json'
        );
        $settingsRepo->setGroup('playground', array_merge($settingsRepo->group('playground'), [
            'gitRepoUrl' => 'https://github.com/acme/widgets',
            'gitRef' => 'main',
            'enabledPacks' => 'paginium-starter',
        ]));
        $playgroundSettings = new PlaygroundSettings($settingsRepo, new DemoMode());
        $registry = new PlaygroundPackRegistry(
            dirname(__DIR__, 3) . '/app/Modules/Playground/Resources/packs',
            $this->baseDir . '/data/playground-packs.json',
            $playgroundSettings
        );
        $scanner = new UntrustedPolicyScanner(
            new CodePolicyEngine($settingsRepo, new SyntaxChecker(), new SecurityScanner())
        );
        $downloader = new class ($bytes) implements PlaygroundArchiveDownloaderInterface {
            public function __construct(private string $payload)
            {
            }

            public function download(string $url, string $token): string
            {
                unset($url, $token);

                return $this->payload;
            }
        };

        return new PlaygroundPackGitImporter(
            $playgroundSettings,
            $registry,
            $downloader,
            new ZipEntryGuard(),
            $scanner,
            $settingsRepo
        );
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }
        @rmdir($dir);
    }
}
