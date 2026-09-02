<?php

declare(strict_types=1);

// config simple, sin secretos: las credenciales de DB siguen viniendo por
// environment del compose (ver App\Core\Database), no de este archivo
return [
    'nombre' => 'php-mvc-docker-starter-apache-mysql',
    'debug' => (bool) (getenv('APP_DEBUG') ?: false),
];
