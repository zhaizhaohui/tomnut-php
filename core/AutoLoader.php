<?php

namespace Core;

class Autoloader
{
    protected static array $map = [];

    // 注册命名空间映射
    public static function register(): void
    {
        spl_autoload_register([self::class, 'load']);
    }

    // 添加映射
    public static function add(string $namespace, string $path): void
    {
        self::$map[trim($namespace, '\\')] = rtrim($path, '/') . '/';
    }

    // 加载类文件
    public static function load(string $class): void
    {
        foreach (self::$map as $namespace => $path) {
            if (str_starts_with($class, $namespace . '\\')) {
                $relative = substr($class, strlen($namespace) + 1);
                $file = $path . str_replace('\\', '/', $relative) . '.php';
                if (is_file($file)) {
                    require $file;
                    return;
                }
            }
        }
    }
}