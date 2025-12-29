<?php

namespace Redium\Cache;

class FileCache implements CacheInterface
{
    private string $cacheDir;

    public function __construct(?string $cacheDir)
    {
        $this->cacheDir = $cacheDir ?? sys_get_temp_dir() . '/redium_cache';

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return $default;
        }

        $data = unserialize(file_get_contents($file));

        // Check if expired
        if ($data['expires_at'] < time()) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $file = $this->getFilePath($key);

        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl
        ];

        return file_put_contents($file, serialize($data)) !== false;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function delete(string $key): bool
    {
        $file = $this->getFilePath($key);

        if (file_exists($file)) {
            return unlink($file);
        }

        return false;
    }

    public function clear(): bool
    {
        $files = glob($this->cacheDir . '/*');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    private function getFilePath(string $key): string
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
}
