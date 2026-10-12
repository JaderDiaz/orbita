# Orbita

Sistema de gestión para agencia de desarrollo: cotizaciones, proyectos, fases, tareas,
tiempo, facturación y rentabilidad.

- Alcance, roles y hoja de ruta: `agent-os/product/`
- Estándares técnicos del proyecto: `agent-os/standards/`

## Stack

PHP 8.5 · Laravel 13 · PostgreSQL 18 · Redis 8 · Inertia 3 + React 19 + TypeScript ·
Tailwind 4 · Vite 8 · Pest

## Requisitos locales

Docker con Colima. La VM necesita montar el directorio del proyecto y suficientes recursos:

```bash
# en ~/.colima/default/colima.yaml
cpu: 4
memory: 8
mounts:
  - location: /private/var/www
    writable: true
```

Los montajes de Colima solo se aplican al arrancar la VM: tras editar el archivo,
`colima stop && colima start`.

## Levantar el entorno

```bash
make install     # primera vez: construye, levanta y migra
make user        # crea un usuario para poder entrar
```

Después, en el día a día:

```bash
make up          # levantar
make down        # detener
make logs        # ver logs
make ps          # estado de los servicios
```

`make` sin argumentos lista todos los comandos disponibles.

El entrypoint del contenedor `app` instala dependencias de Composer la primera vez (viven en
un volumen nombrado), copia `.env` desde `.env.example` si falta y corre las migraciones.

Son tres servicios y nada más: `app`, `postgres` y `vite`.

| Servicio | URL / puerto |
|---|---|
| Aplicación | http://localhost:8100 |
| Vite (HMR) | http://localhost:5274 |
| PostgreSQL | `localhost:5433` · usuario `orbita` / clave `orbita` |

Dos bases lógicas en el mismo contenedor: `orbita` para desarrollo y `orbita_test` para la
suite. Están separadas porque `RefreshDatabase` ejecuta `migrate:fresh`, así que una suite
apuntando a `orbita` borraría los datos de desarrollo en cada corrida. El test
`TestDatabaseTargetTest` lo vigila.

Cache, sesiones y colas usan el driver `database`, y el correo saliente va al log. No hay
Redis ni servidor de correo en el stack: se agregan si la carga lo justifica.

Los puertos publicados evitan a propósito los valores por defecto (8080, 5173, 5432) para no
chocar con otros proyectos. Son parametrizables:

```bash
make up ORBITA_APP_PORT=9100
```

Variables disponibles: `ORBITA_APP_PORT`, `ORBITA_VITE_PORT`, `ORBITA_DB_PORT`.
Para fijarlas, decláralas en `.env`, que Compose también lee.

`make urls` muestra las URLs con los puertos vigentes y `make ports` lista qué puertos del
host ya están ocupados.

La base de tests corre sobre `tmpfs`: se recrea en cada arranque y nunca compite con la
base de desarrollo.

## Desarrollo

```bash
make check       # todas las compuertas de calidad
make test        # suite completa
make test only=SecurityTest
make pint        # corregir estilo
make stan        # analisis estatico
make shell       # shell dentro del contenedor
make psql        # psql contra la base de desarrollo
make psql-test   # psql contra la base de la suite
make tinker
make routes
```

Comandos arbitrarios:

```bash
make artisan cmd="migrate:status"
make composer cmd="require spatie/laravel-data"
```

Destructivos, piden confirmación:

```bash
make fresh       # borra y recrea la base de desarrollo
make destroy     # borra contenedores y volumenes
```

## Autenticación

Login con correo y contraseña, más 2FA. **No hay registro público ni recuperación de
contraseña**: los usuarios los provisiona un Administrador.

Al desactivar un feature de Fortify hay que quitar también sus referencias en
`resources/js`, porque Wayfinder genera las rutas en tiempo de build y una referencia
huérfana rompe `pnpm run build`.
