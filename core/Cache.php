<?php

namespace Core;

class Cache
{
    protected static string $path = '';
    protected static int $ttl = 3600;

    public static function init(string $path, int $ttl = 3600): void
    {
        self::$path = rtrim($path, '/') . '/';
        self::$ttl  = $ttl;
        if (!is_dir(self::$path)) {
            mkdir(self::$path, 0755, true);
        }
    }

    protected static function file(string $key): string
    {
        return self::$path . md5($key) . '.cache';
    }

    public static function set(string $key, $value, ?int $ttl = null): bool
    {
        $ttl  = $ttl ?? self::$ttl;
        $data = [
            'expire' => time() + $ttl,
            'value'  => $value,
        ];
        return file_put_contents(self::file($key), serialize($data)) !== false;
    }

    public static function get(string $key, $default = null)
    {
        $file = self::file($key);
        if (!is_file($file)) {
            return $default;
        }
        $data = unserialize(file_get_contents($file));
        if (!is_array($data) || $data['expire'] < time()) {
            @unlink($file);
            return $default;
        }
        return $data['value'];
    }

    public static function has(string $key): bool
    {
        return self::get($key, '__miss__') !== '__miss__';
    }

    public static function delete(string $key): bool
    {
        $file = self::file($key);
        return is_file($file) ? unlink($file) : true;
    }

    public static function clear(): void
    {
        foreach (glob(self::$path . '*.cache') as $file) {
            @unlink($file);
        }
    }
}