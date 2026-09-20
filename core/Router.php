<?php

namespace Core;

class Router
{
    protected array $routes = [
        'GET'    => [],
        'POST'   => [],
        'PUT'    => [],
        'DELETE' => [],
    ];

    public function get(string $path, $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function delete(string $path, $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    protected function add(string $method, string $path, $handler): void
    {
        $pattern = preg_replace_callback(
            '#:(\w+)(?:\(([^)]+)\))?#',
            function ($m) {
                $name  = $m[1];
                $regex = $m[2] ?? '[^/]+'; 
                return '(?P<' . $name . '>' . $regex . ')';
            },
            $path
        );

        $pattern = '#^' . rtrim($pattern, '/') . '/?$#';
        $this->routes[$method][] = compact('pattern', 'handler');
    }

    public function dispatch(string $method, string $uri)
    {
        $uri = parse_url($uri, PHP_URL_PATH) ?? '/';

        foreach ($this->routes[$method] ?? [] as $route) {
            $result = @preg_match($route['pattern'], $uri, $matches);
            if ($result === 1) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return $this->call($route['handler'], $params);
            }
        }

        http_response_code(404);
        return '404 Not Found';
    }

    protected function call($handler, array $params)
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class();
            return call_user_func_array([$controller, $method], $params);
        }
        return call_user_func_array($handler, $params);
    }
}