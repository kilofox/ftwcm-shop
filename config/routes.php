<?php

use Hyperf\HttpServer\Router\Router;
use Ftwcm\Shop\Controller\AttrController;

Router::addGroup('/ftwcm/shop/', function () {
    Router::get('attribute', [AttrController::class, 'attribute']);
    Router::post('attribute', [AttrController::class, 'attribute']);
    Router::put('attribute', [AttrController::class, 'attribute']);
    Router::delete('attribute', [AttrController::class, 'attribute']);
});
