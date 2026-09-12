<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Upload;

use PaginiumCMS\Core\Security\Services\ZipEntryGuard;
use ZipArchive;

/**
 * Zip-Slip and zip-bomb guards for archive upload profiles (It.78).
 */
final class UploadArchiveValidator
{
    private const MAX_ENTRIES = 50_000;

    private const MAX_UNCOMPRESSED_BYTES = 524_288_000;

    private const MAX_COMPRESSION_RATIO = 200;

    public function __construct(
        private ZipEntryGuard $zipGuard
    ) {
    }

    public function assertSafeArchive(string $zipPath, int $compressedSizeBytes): void
    {
        if (!is_file($zipPath)) {
            throw new UploadPolicyException('Archív neexistuje');
        }

        if (!class_exists(ZipArchive::class)) {
            throw new UploadPolicyException('ZipArchive PHP rozšírenie nie je dostupné');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new UploadPolicyException('Neplatný ZIP archív');
        }

        try {
            $entryCount = $zip->numFiles;
            if ($entryCount > self::MAX_ENTRIES) {
                throw new UploadPolicyException('ZIP archív obsahuje príliš veľa záznamov');
            }

            $uncompressedTotal = 0;

            for ($index = 0; $index < $entryCount; $index++) {
                $entryName = (string) $zip->getNameIndex($index);
                if (!$this->zipGuard->isSafeEntry($entryName)) {
                    throw new UploadPolicyException('Nebezpečný ZIP záznam odmietnutý');
                }

                $stat = $zip->statIndex($index);
                if (!is_array($stat)) {
                    continue;
                }

                $uncompressed = (int) $stat['size'];
                if ($uncompressed < 0) {
                    throw new UploadPolicyException('Neplatný ZIP záznam');
                }

                $uncompressedTotal += $uncompressed;
                if ($uncompressedTotal > self::MAX_UNCOMPRESSED_BYTES) {
                    throw new UploadPolicyException('ZIP archív presahuje maximálnu rozbalenú veľkosť');
                }
            }

            if ($compressedSizeBytes > 0 && $uncompressedTotal > 0) {
                $ratio = (int) ceil($uncompressedTotal / max(1, $compressedSizeBytes));
                if ($ratio > self::MAX_COMPRESSION_RATIO) {
                    throw new UploadPolicyException('ZIP archív má podozrivý kompresný pomer');
                }
            }
        } finally {
            $zip->close();
        }
    }
}
