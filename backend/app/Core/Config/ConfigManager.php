<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Config;

class ConfigManager
{
    private array $config = [];
    private array $loaded = [];

    public function load(string $file): void
    {
        if (isset($this->loaded[$file])) {
            return;
        }

        $path = __DIR__ . '/../../../config/' . $file . '.php';
        if (file_exists($path)) {
            $config = require $path;
            if (is_array($config)) {
                $this->config[$file] = $config;
                $this->loaded[$file] = true;
            }
        }
    }

    public function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $config = $this->config;

        foreach ($parts as $part) {
            if (!isset($config[$part])) {
                return $default;
            }
            $config = $config[$part];
        }

        return $config;
    }

    public function set(string $key, $value): void
    {
        $parts = explode('.', $key);
        $config = &$this->config;

        foreach ($parts as $i => $part) {
            if ($i === count($parts) - 1) {
                $config[$part] = $value;
            } else {
                if (!isset($config[$part])) {
                    $config[$part] = [];
                }
                $config = &$config[$part];
            }
        }
    }

    public function merge(string $file, array $config): void
    {
        if (isset($this->config[$file])) {
            $this->config[$file] = array_merge_recursive($this->config[$file], $config);
        } else {
            $this->config[$file] = $config;
        }
        $this->loaded[$file] = true;
    }
}
