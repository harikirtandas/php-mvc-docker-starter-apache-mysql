# php-mvc-docker-starter-apache-mysql

- Es el hermano MVC de `php-docker-starter-apache-mysql`: mismo andamiaje Docker
  al pie de la letra (Dockerfile, vhost, php.ini, docker-compose.yml sin tocar).
  La diferencia entera esta dentro de `src/`: Controllers/Models/Views, autoload
  por Composer (PSR-4, `App\` -> `app/`) y un router propio con parametros
  dinamicos, en vez del `index.php` de demo unico del starter plano.
- **Solo `src/public/` es el DocumentRoot.** Cualquier cosa que Apache no deba
  servir por URL directa va un nivel arriba, en `src/app/`. Poner `Models/`
  dentro de `public/` (o cualquier otra carpeta de `app/`) la expondria por HTTP.
- **`make install` es obligatorio antes del primer arranque**: sin `vendor/` no
  existe el autoloader de Composer (`vendor/autoload.php`) y el proyecto no corre
  con solo `docker compose up`. El guard de tres ramas de `install` (composer.json
  sin vendor / vendor ya instalado / sin composer.json) es el mismo del starter
  plano, no se toco.
- `composer.json` vive en `src/composer.json`, **no en la raiz del repo**: el bind
  mount es `./src:/var/www/html`, asi que `vendor/` tiene que estar dentro de
  `src/` para que el contenedor lo vea.
- Convencion de mayusculas en los directorios de `src/app/`: `Controllers/`,
  `Models/`, `Views/`, `Core/` con mayuscula inicial (mapean namespaces PSR-4),
  `config/` y `public/css`/`public/js` en minuscula (no mapean namespace). Es
  deliberado: en Mac el filesystem es case-insensitive y el contenedor Debian no
  — un mismatch de case anda en local y falla dentro del contenedor.
- Las rutas se registran en `src/public/index.php` (unico lugar del repo donde se
  arma la tabla de rutas) y se despachan con `$router->dispatch()`. Las rutas
  **literales van antes que las parametricas** (`/items/nuevo` antes que
  `/items/{id}`), ver la seccion "Rutas" de `README.md` para el detalle completo
  con ejemplos de que se rompe si se invierte el orden.
- **PSR-4 no autocarga funciones, solo clases.** Si mas adelante hacen falta
  helpers globales, van en `src/app/Core/helpers.php` declarado en la seccion
  `"files"` de `composer.json` (no en `"psr-4"`, que solo resuelve clases por
  namespace).
- `App\Core\Router` y `App\Core\Controller` son minimos y estan pensados para
  crecer (agregar middleware, grupos, rutas con nombre) sin desarmar lo que ya
  funciona — no pretenden ser un framework.
- `App\Core\Database::connection()` reemplaza a la funcion `db()` del starter
  plano: mismo comportamiento (singleton estatico, credenciales con `getenv()` y
  nunca `$_ENV` porque `variables_order` no siempre incluye `E` por default,
  `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `EMULATE_PREPARES` en false), ahora como
  metodo estatico de una clase en vez de una funcion global.
- El schema de `docker/mysql/init/01-schema.sql` (tabla `demo_items`, sin tocar)
  solo corre en el primer arranque del volumen `mysql-data`. Para reaplicarlo hace
  falta recrear el volumen: `make fresh` (borra los datos existentes). Para sumar
  tablas/columnas nuevas SIN perder datos: agregar el `.sql` numerado en
  `docker/mysql/init/` y aplicarlo a la base ya corriendo con
  `make db-import FILE=docker/mysql/init/0N-nombre.sql` (no espera a un `fresh`).
- Todo comando (composer, php, lo que sea) corre via `docker compose exec app ...`,
  `make shell` o `make composer CMD="..."`. No hay PHP instalado en el host a
  proposito.
- El Dockerfile **no agrega `USER`**: Apache necesita arrancar como root para
  poder bindear el puerto 80, y el propio proceso maestro baja los workers a
  `www-data` solo. Los build args `UID`/`GID` remapean `www-data` (via
  `usermod`/`groupmod`) en vez de crear un usuario nuevo, porque `www-data` ya
  existe en la imagen base. (Heredado sin cambios del starter plano.)
- Vertical slice demo, descartable como conjunto: `Item::todos()`/`Item::buscar()`
  en el modelo, `HomeController@index` (ruta `/`, literal) y
  `ItemController@show` (ruta `/items/{id:\d+}`, parametrica con constraint
  numerico) en los controladores. Sirve para probar de punta a punta que Apache,
  PHP, el router, MySQL y el autoloader funcionan juntos — no para copiar el
  dominio "items" a un proyecto real.
