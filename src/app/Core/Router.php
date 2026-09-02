<?php

declare(strict_types=1);

namespace App\Core;

// router minimo propio, sin librerias externas. soporta parametros dinamicos
// en el path con sintaxis de llaves: /items/{id} o /items/{id:\d+} (constraint
// opcional despues de los dos puntos; sin constraint el default es [^/]+, es
// decir cualquier cosa menos una barra, para no cruzar segmentos del path)
final class Router
{
    /**
     * @var array<int, array{
     *     metodo: string,
     *     accion: string,
     *     regex: string,
     *     params: array<string, bool>
     * }>
     */
    private array $rutas = [];

    public function get(string $path, string $accion): void
    {
        $this->agregar('GET', $path, $accion);
    }

    public function post(string $path, string $accion): void
    {
        $this->agregar('POST', $path, $accion);
    }

    private function agregar(string $metodo, string $path, string $accion): void
    {
        // la regex se compila UNA vez, al registrar la ruta, y queda
        // cacheada en $this->rutas: dispatch() nunca recompila nada en el
        // loop de matching, solo lee lo que ya esta compilado aca
        [$regex, $params] = $this->compilar($path);

        $this->rutas[] = [
            'metodo' => $metodo,
            'accion' => $accion,
            'regex' => $regex,
            'params' => $params,
        ];
    }

    /**
     * @return array{0: string, 1: array<string, bool>}
     */
    private function compilar(string $path): array
    {
        // separa el path en partes literales y placeholders {nombre} o
        // {nombre:constraint}, preservando los delimitadores gracias a
        // PREG_SPLIT_DELIM_CAPTURE
        $tokens = preg_split('#(\{\w+(?::[^}]+)?\})#', $path, -1, PREG_SPLIT_DELIM_CAPTURE);

        $regex = '';
        $params = [];

        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }

            if (preg_match('#^\{(\w+)(?::([^}]+))?\}$#', $token, $m) === 1) {
                $nombre = $m[1];
                $constraint = $m[2] ?? '[^/]+';
                $regex .= '(?<' . $nombre . '>' . $constraint . ')';

                // si el constraint es puramente numerico (\d+ o \d), el
                // valor capturado se castea a int en ejecutar(): con
                // declare(strict_types=1) un metodo que declara int $id
                // explota si en cambio recibe el string "42"
                $params[$nombre] = $constraint === '\d+' || $constraint === '\d';

                continue;
            }

            // parte literal: se escapa con preg_quote ANTES de armar la
            // regex final, para que un punto (u otro caracter especial de
            // regex) en la URL no matchee cualquier caracter
            $regex .= preg_quote($token, '#');
        }

        return ['#^' . $regex . '$#', $params];
    }

    public function dispatch(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path = $path === false || $path === null ? '/' : $path;
        $metodoHttp = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $metodosPermitidos = [];

        // el match es por orden de registro: la PRIMERA ruta cuyo path Y
        // metodo coinciden gana. por eso las rutas literales (/items/nuevo)
        // tienen que registrarse ANTES que las parametricas (/items/{id}):
        // si {id} se registra primero, matchea "nuevo" como si fuera un id
        // y la ruta literal queda inalcanzable
        foreach ($this->rutas as $ruta) {
            if (preg_match($ruta['regex'], $path, $m) !== 1) {
                continue;
            }

            if ($ruta['metodo'] !== $metodoHttp) {
                // el path matcheo pero con otro metodo HTTP: se sigue
                // buscando (puede haber otra ruta con el mismo path y el
                // metodo correcto), y si nada matchea del todo se responde
                // 405 en vez de 404
                $metodosPermitidos[] = $ruta['metodo'];
                continue;
            }

            $this->ejecutar($ruta, $m);
            return;
        }

        if ($metodosPermitidos !== []) {
            http_response_code(405);
            header('Allow: ' . implode(', ', array_unique($metodosPermitidos)));
            return;
        }

        http_response_code(404);
        require __DIR__ . '/../Views/errors/404.php';
    }

    /**
     * @param array{metodo: string, accion: string, regex: string, params: array<string, bool>} $ruta
     * @param array<string|int, string> $matches
     */
    private function ejecutar(array $ruta, array $matches): void
    {
        // los valores capturados se pasan al metodo del controlador como
        // argumentos posicionales, en el orden en que aparecen en el path
        // (no como array asociativo)
        $args = [];

        foreach ($ruta['params'] as $nombre => $esNumerico) {
            $valor = $matches[$nombre];
            $args[] = $esNumerico ? (int) $valor : $valor;
        }

        [$controlador, $metodo] = explode('@', $ruta['accion']);
        $clase = 'App\\Controllers\\' . $controlador;

        $instancia = new $clase();
        $instancia->$metodo(...$args);
    }
}
