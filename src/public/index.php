<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;

$config = require __DIR__ . '/../config/app.php';

if ($config['debug']) {
    ini_set('display_errors', '1');
}

$router = new Router();

// las rutas literales van ANTES que las parametricas: si /items/nuevo
// existiera, tendria que registrarse antes de /items/{id:\d+} o el router
// nunca la alcanza (ver comentario en Router::dispatch)
$router->get('/', 'HomeController@index');
$router->get('/items/{id:\d+}', 'ItemController@show');

$router->dispatch();
