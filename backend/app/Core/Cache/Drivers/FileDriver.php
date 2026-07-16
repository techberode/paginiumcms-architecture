<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache\Drivers;

use PaginiumCMS\Support\FileHelper;
use PaginiumCMS\Support\JsonHelper;

class FileDriver implements DriverInterface
{
    private string $path;
    private string $hashAlgo;

    public function __construct(string $path, string $hashAlgo = 'sha256')
    {
        $this->path = rtrim($path, '/');
        $this->hashAlgo = $hashAlgo;
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return $default;
        }

        $data = JsonHelper::decode(FileHelper::read($file));
        if ($data['expires'] !== null && (int) $data['expires'] < time()) {
            unlink($file);

            return $default;
        }

        return $data['value'] ?? $default;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $file = $this->getFilePath($key);
        $data = ['value' => $value, 'expires' => $ttl ? time() + $ttl : null, 'created' => time()];

        return file_put_contents($file, JsonHelper::encode($data)) !== false;
    }

    public function delete(string $key): bool
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            return unlink($file);
        }

        return true;
    }

    public function clear(): bool
    {
        foreach (glob($this->path . '/*.cache') ?: [] as $file) {
            unlink($file);
        }

        return true;
    }

    public function has(string $key): bool
    {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return false;
        }

        $data = JsonHelper::decode(FileHelper::read($file));
        if ($data['expires'] !== null && (int) $data['expires'] < time()) {
            unlink($file);

            return false;
        }

        return true;
    }

    /**
     * Atomický increment cez flock (bez race pri rate limite).
     */
    public function increment(string $key, int $step = 1, ?int $ttl = null): int
    {
        $file = $this->getFilePath($key);
        $lockFile = $file . '.lock';
        $handle = fopen($lockFile, 'c+');
        if ($handle === false) {
            $current = (int) $this->get($key, 0);
            $new = $current + $step;
            $this->set($key, $new, $ttl);

            return $new;
        }

        try {
            flock($handle, LOCK_EX);
            $current = (int) $this->get($key, 0);
            $new = $current + $step;
            $this->set($key, $new, $ttl);

            return $new;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function getFilePath(string $key): string
    {
        return $this->path . '/' . hash($this->hashAlgo, $key) . '.cache';
    }
}
