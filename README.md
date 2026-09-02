# php-mvc-docker-starter-apache-mysql

Plantilla de GitHub para levantar un proyecto PHP con un esqueleto **MVC minimo
propio** (sin framework, sin librerias externas) dockerizado, con **Apache +
mod_php + MySQL 8**, en cualquier maquina, con un solo comando. Es el hermano MVC
de [`php-docker-starter-apache-mysql`](../php-docker-starter-apache-mysql): mismo
andamiaje Docker, pero con Controllers/Models/Views, autoload por Composer y un
router propio con soporte de parametros dinamicos.

## Requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (o Docker Engine + Compose plugin) corriendo.
- [GitHub CLI](https://cli.github.com/) (`gh`) para crear proyectos nuevos desde la terminal. Alternativa: boton **"Use this template"** en GitHub.

## Crear un proyecto nuevo desde este template

```bash
gh repo create mi-proyecto --template TU_USUARIO/php-mvc-docker-starter-apache-mysql --private --clone
cd mi-proyecto
make install
```

Al terminar, la app esta en **http://localhost:8080** y Adminer en **http://localhost:8081**.

`make install` es **obligatorio** antes del primer arranque: sin `vendor/` no
existe el autoloader de Composer y el proyecto no corre con solo `docker compose up`.

## Arquitectura

Un solo contenedor de aplicacion (`php:8.4-apache`, mod_php) mas MySQL y Adminer:

| Servicio | Imagen | Rol |
|---|---|---|
| `app` | build propio, `php:8.4-apache` | Apache y PHP en el mismo proceso. Publica `APP_PORT` (default 8080). |
| `mysql` | `mysql:8` | Base de datos, volumen persistente `mysql-data` + healthcheck. `app` espera a que este *healthy* antes de arrancar. |
| `adminer` | `adminer` | Cliente web de MySQL, publica `ADMINER_PORT` (default 8081). |

`./src` se monta como bind mount en `app`: `src/public` es el docroot (unico
directorio que Apache expone por URL), el resto de `src/app`, `src/config` y
`vendor/` quedan fuera del docroot.

## Estructura de `src/`

```
src/
├── app/
│   ├── Controllers/       # HomeController, ItemController
│   ├── Models/             # Item
│   ├── Views/
│   │   ├── layouts/main.php
│   │   ├── home/index.php
│   │   ├── items/show.php
│   │   └── errors/404.php
│   └── Core/               # Router, Controller (base), Database
├── config/app.php
├── composer.json           # autoload PSR-4: App\ -> app/
└── public/
    ├── .htaccess
    ├── index.php            # front controller
    ├── css/ js/ img/
```

## Rutas

El router propio (`App\Core\Router`) vive en `src/app/Core/Router.php`. Se registran
rutas en `src/public/index.php` con `$router->get(...)`/`$router->post(...)`, y se
despachan con `$router->dispatch()`.

### Sintaxis

| Path registrado | Que matchea | Que recibe el metodo del controlador |
|---|---|---|
| `/items` | Solo ese literal | Nada (sin parametros) |
| `/items/{id}` | Cualquier segmento no vacio sin `/` | `string $id` |
| `/items/{id:\d+}` | Solo digitos | `int $id` (casteado, ver mas abajo) |
| `/items/{id}/editar` | Parametro en el medio del path | `string $id` (o `int` con constraint) |
| `/cat/{slug}/items/{id:\d+}` | Multiples parametros | `string $slug, int $id`, en ese orden |

### Ejemplo de registro

```php
// src/public/index.php
$router->get('/', 'HomeController@index');
$router->get('/items/{id:\d+}', 'ItemController@show');
```

```php
// src/app/Controllers/ItemController.php
final class ItemController extends Controller
{
    public function show(int $id): void
    {
        // $id ya llega como int, no como string
    }
}
```

### El orden de registro importa

El router matchea por **orden de registro**: la primera ruta cuyo path y metodo
HTTP coinciden gana. Las rutas **literales tienen que ir antes** que las
**parametricas** con el mismo prefijo, o quedan inalcanzables:

```php
// CORRECTO: /items/nuevo se registra antes, asi que gana el match cuando
// la URL es literalmente "/items/nuevo"
$router->get('/items/nuevo', 'ItemController@formulario');
$router->get('/items/{id}', 'ItemController@show');

// INCORRECTO: {id} sin constraint matchea CUALQUIER segmento, incluida la
// palabra "nuevo" (el patron por defecto es [^/]+). Como esta ruta se
// registro primero, una request a /items/nuevo termina en
// ItemController@show('nuevo') en vez de @formulario(), y esa segunda linea
// queda inalcanzable
$router->get('/items/{id}', 'ItemController@show');
$router->get('/items/nuevo', 'ItemController@formulario');
```

### Casteo de parametros numericos

Si el constraint de un parametro es puramente numerico (`\d+` o `\d`), el valor
capturado se castea a `int` antes de pasarlo al controlador; cualquier otro
constraint (o la ausencia de uno) llega como `string`. Esto importa porque el
proyecto usa `declare(strict_types=1)`: un metodo que declara `int $id` explota
con un `TypeError` si en cambio recibe el string `"42"`.

### Que NO hace este router (a proposito)

- No hay rutas con nombre ni un metodo `url()` para generarlas.
- No hay middleware.
- No hay grupos de rutas ni prefijos compartidos.
- No soporta parametros opcionales (`{id?}`).

Es minimo a proposito, para poder crecer agregando estas piezas mas adelante sin
tener que desarmar lo que ya funciona.

## Comandos disponibles (Makefile)

| Comando | Que hace |
|---|---|
| `make install` | Instala dependencias de Composer si hace falta, levanta los tres contenedores. Correlo una sola vez por proyecto (obligatorio: sin esto no hay autoloader). |
| `make up` | Levanta los contenedores en segundo plano. |
| `make down` | Apaga los contenedores. **No borra datos**: `mysql-data` es un volumen con nombre. |
| `make restart` | Reinicia los contenedores sin rebuildear. |
| `make shell` | Abre una terminal `bash` dentro del contenedor `app`. |
| `make db-shell` | Abre el cliente `mysql` conectado a la base del proyecto. |
| `make logs` | Sigue los logs de todos los servicios. |
| `make db-import FILE=dump.sql` | Aplica un `.sql` a la base ya corriendo (dump completo o cambios incrementales), sin recrear el volumen ni perder datos. |
| `make fresh` | Borra el volumen de MySQL y vuelve a levantar todo de cero (reaplica todo `docker/mysql/init/*.sql`). Pide confirmacion explicita. |
| `make composer CMD="require x/y"` | Corre cualquier comando de Composer dentro del contenedor `app`. |

## Configuracion (`.env` en la raiz, no en `src/`)

Las credenciales de MySQL y los puertos se controlan con variables de entorno que lee `docker-compose.yml`, no con un `.env` dentro de `src/`. Para cambiarlas, creá un `.env` en la raiz del repo:

```bash
APP_PORT=8080
ADMINER_PORT=8081
DB_DATABASE=app
DB_USERNAME=app
DB_PASSWORD=secret
```

`App\Core\Database::connection()` lee estas mismas variables con `getenv()` dentro
del contenedor `app` (docker-compose las inyecta como `environment:`), no hace
falta editar codigo para cambiarlas.

## Agregar tablas nuevas sin perder datos

`docker/mysql/init/*.sql` solo corre en el primer arranque del volumen `mysql-data` (ver tabla de arriba: `make fresh` es lo unico que los reaplica, y de paso borra todo). Para sumar una tabla o columna a un proyecto que ya tiene datos cargados, sin tocar lo que ya hay:

1. Guardá el archivo en `docker/mysql/init/`, con el siguiente numero de orden: `02-nombre-descriptivo.sql`, `03-otra-cosa.sql`, etc. (segui la numeracion de `01-schema.sql`).
2. Aplicalo a la base que ya esta corriendo, sin pasar por `make fresh`:
   ```bash
   make db-import FILE=docker/mysql/init/02-nombre-descriptivo.sql
   ```

`make db-import` no distingue entre "restaurar un dump" y "aplicar un cambio incremental": en los dos casos le pipea el archivo tal cual al cliente `mysql` contra la base ya corriendo. Lo que cambia es el contenido del `.sql` — `CREATE TABLE IF NOT EXISTS`/`ALTER TABLE` para no pisar lo que ya existe.

## Arrancar un proyecto real

1. `gh repo create mi-proyecto --template TU_USUARIO/php-mvc-docker-starter-apache-mysql --private --clone && cd mi-proyecto`
2. Reemplazar `docker/mysql/init/01-schema.sql` por el schema real del proyecto (o agregar mas archivos `.sql` numerados).
3. Borrar el vertical slice demo (`Item`, `HomeController`, `ItemController`, las vistas de `home/` e `items/`) y reemplazarlo por los Controllers/Models/Views propios del proyecto.
4. `make install` — levanta todo e instala las dependencias de Composer.
