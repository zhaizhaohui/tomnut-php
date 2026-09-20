<?php

namespace Core;

class App
{
    protected array $config = [];

    public function __construct()
    {
        $this->config = require __DIR__ . '/../config/config.php';

        // 初始化各模块
        Cache::init($this->config['cache_path'], $this->config['cache_ttl']);
        View::init(
            $this->config['view_path'],
            $this->config['cache_path'],
            $this->config['debug']
        );
        Database::init($this->config['db']);   // ← 新增
    }

    public function run(): void
    {
        $router = new Router();
        require __DIR__ . '/../routes/web.php';

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';

        echo $router->dispatch($method, $uri);
    }
}