<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache\Drivers;

class FileDriver implements DriverInterface
{
    private string $path;
    private string $hashAlgo;

    public function __construct(string $path, string $hashAlgo = 'sha256')
    {
        $this->path = rtrim($path, '/');
        $this->hashAlgo = $hashAlgo;
        if (!is_dir($this->path)) mkdir($this->path, 0755, true);
    }

    public function get(string $key, $default = null)
    {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) return $default;
        $data = json_decode(file_get_contents($file), true);
        if ($data === null) return $default;
        if ($data['expires'] !== null && $data['expires'] < time()) { unlink($file); return $default; }
        return $data['value'];
    }

    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $file = $this->getFilePath($key);
        $data = ['value' => $value, 'expires' => $ttl ? time() + $ttl : null, 'created' => time()];
        return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)) !== false;
    }

    public function delete(string $key): bool
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) return unlink($file);
        return true;
    }

    public function clear(): bool
    {
        foreach (glob($this->path . '/*.cache') as $file) unlink($file);
        return true;
    }

    public function has(string $key): bool
    {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) return false;
        $data = json_decode(file_get_contents($file), true);
        if ($data === null) return false;
        if ($data['expires'] !== null && $data['expires'] < time()) { unlink($file); return false; }
        return true;
    }

    private function getFilePath(string $key): string
    {
        return $this->path . '/' . hash($this->hashAlgo, $key) . '.cache';
    }
}
