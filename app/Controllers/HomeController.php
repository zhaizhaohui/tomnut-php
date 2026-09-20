<?php

namespace App\Controllers;

use Core\View;
use Core\Cache;
use Utils\Helper;

class HomeController
{
    public function index(): string
    {
        // 演示缓存
        $name = Cache::get('home_name');
        if ($name === null) {
            $name = Helper::upper('world');
            Cache::set('home_name', $name, 60);
        }

        return View::render('home', ['name' => $name]);
    }
}