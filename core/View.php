<?php

namespace Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class View
{
    protected static ?Environment $twig = null;

    public static function init(string $viewPath, string $cachePath, bool $debug = false): void
    {
        $loader = new FilesystemLoader($viewPath);

        self::$twig = new Environment($loader, [
            'cache'       => $debug ? false : $cachePath,
            'debug'       => $debug,
            'auto_reload' => $debug,
        ]);
    }

    public static function render(string $template, array $data = []): string
    {
        // 若未指定后缀，自动补 .twig
        if (!str_contains($template, '.')) {
            $template .= '.twig';
        }

        return self::$twig->render($template, $data);
    }
}