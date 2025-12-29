<?php

namespace Redium\Cache;

class Cache
{
    private static ?CacheInterface $driver = null;

    public static function setDriver(CacheInterface $driver): void
    {
        self::$driver = $driver;
    }

    public static function getDriver(): CacheInterface
    {
        if (self::$driver === null) {
            self::$driver = new FileCache();
        }

        return self::$driver;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::getDriver()->get($key, $default);
    }

    public static function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        return self::getDriver()->set($key, $value, $ttl);
    }

    public static function has(string $key): bool
    {
        return self::getDriver()->has($key);
    }

    public static function delete(string $key): bool
    {
        return self::getDriver()->delete($key);
    }

    public static function clear(): bool
    {
        return self::getDriver()->clear();
    }

    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        return self::getDriver()->remember($key, $ttl, $callback);
    }

    public static function forget(string $key): bool
    {
        return self::delete($key);
    }
}
