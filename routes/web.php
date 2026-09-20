<?php

use App\Controllers\HomeController;

/** @var Core\Router $router */

$router->get('/', [HomeController::class, 'index']);
$router->get('/show', [HomeController::class, 'show']);