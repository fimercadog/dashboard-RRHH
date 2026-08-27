# Despliegue En Hostinger

Guia de referencia de como quedo desplegada la aplicacion, para no repetir
el diagnostico completo la proxima vez. Cubre por que el login no conectaba,
y los pasos exactos que lo arreglaron.

## El problema real (y por que no era CORS al principio)

Hostinger tiene **dos paneles de hosting distintos** en la misma cuenta:

1. **"Sitios web"** (el panel conectado a GitHub, con "Preajuste del marco").
   Solo despliega apps de **Node.js** (Next.js, Nuxt, Express, etc.). No
   tiene opcion de PHP/Laravel en ese dropdown — se confirmo revisando la
   lista completa de frameworks disponibles ahi.
2. **hPanel clasico** (Subdominios, Administrador de archivos, SSH, bases
   de datos). Aqui es donde vive el backend PHP/Laravel.

El frontend (Next.js) se desplego con el primero. El backend (Laravel) con
el segundo. Son procesos completamente separados, en ubicaciones distintas
del servidor.

El primer error (`500 Internal Server Error` al hacer login desde el sitio
publicado) no era CORS: era que `frontend/next.config.ts` tiene un
`rewrite` que manda `/api/*` a `http://127.0.0.1:8001` — eso solo funciona
en local, donde ahi si corre Laravel. En el servidor de Hostinger no hay
nada escuchando en ese puerto, así que Next.js fallaba al hacer el proxy
interno y devolvia un 500 en texto plano (no un error de Laravel).

## Arquitectura final

```text
dfctalentohumano.fidelmercadotech.com   -> Frontend (Next.js, panel "Sitios web")
api-rrhh.fidelmercadotech.com           -> Backend (Laravel, hPanel clasico + SSH)
```

El frontend nunca usa la ruta relativa `/api` en produccion: siempre debe
tener `NEXT_PUBLIC_API_URL` apuntando directo al dominio del backend.

## Paso 1 — Crear el subdominio del backend (hPanel)

hPanel > Sitios web > `fidelmercadotech.com` > Dominios > Subdominios:

- Subdominio: `api-rrhh` (con guion, no guion bajo — los subdominios no
  deberian llevar `_`).
- Marcar **"Carpeta personalizada para subdominio"**.
- Nombre de la carpeta: `api_rrhh_backend/public`

Esto crea `public_html/api_rrhh_backend/public` como *document root* del
subdominio. Solo esa carpeta es visible desde el navegador — el resto del
codigo de Laravel (incluido `.env`) nunca queda expuesto.

## Paso 2 — Acceso SSH

hPanel > Sitios web > `fidelmercadotech.com` > Avanzado > Acceso SSH.
Ahi mismo, en "Claves SSH", se agrega la clave publica generada en la
maquina de trabajo (par de llaves en `~/.ssh/hostinger_apirrhh`, entrada
en hPanel llamada `claude-hostinger-apirrhh`). La clave privada nunca sale
de la maquina local ni se comparte por chat.

Datos de conexion (no son secretos, la seguridad la da la llave):

```text
ssh -p 65002 -i ~/.ssh/hostinger_apirrhh u910322706@82.29.157.42
```

## Paso 3 — Clonar el repo y enlazar la carpeta publica

El subdominio ya creo `public_html/api_rrhh_backend/` como carpeta comun.
En vez de subir archivos sueltos, se clono el repo completo aparte y se
reemplazo esa carpeta por un **symlink** al `backend/` real del repo — asi
un `git pull` en el futuro actualiza el backend sin tocar nada mas.

```bash
cd /home/u910322706/domains/fidelmercadotech.com
git clone --depth 1 https://github.com/fimercadog/dashboard-RRHH.git dashboard-RRHH

cd public_html
rm -rf api_rrhh_backend
ln -s ../dashboard-RRHH/backend api_rrhh_backend
```

(Ojo con la profundidad relativa del symlink: `public_html/` y
`dashboard-RRHH/` son hermanas dentro de `domains/fidelmercadotech.com/`,
por eso es `../dashboard-RRHH/backend` y no `../../...`.)

## Paso 4 — Instalar Laravel en el servidor

```bash
cd /home/u910322706/domains/fidelmercadotech.com/dashboard-RRHH/backend
composer install --no-dev --optimize-autoloader --no-interaction
touch database/database.sqlite
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force   # solo la primera vez, crea usuarios demo
chmod -R 775 storage bootstrap/cache database
```

PHP 8.2.30, Composer 2.9.8 y la extension `pdo_sqlite` ya venian
disponibles en el servidor — no hizo falta pedir nada especial.

### `.env` de produccion usado

```env
APP_NAME="DFC Talento Humano"
APP_ENV=production
APP_KEY=            # lo llena `php artisan key:generate --force`
APP_DEBUG=false
APP_URL=https://api-rrhh.fidelmercadotech.com
FRONTEND_URL=https://dfctalentohumano.fidelmercadotech.com

DB_CONNECTION=sqlite
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
MAIL_MAILER=log
```

`FRONTEND_URL` se usa solo para armar el link de "recuperar contraseña".
Si el frontend cambia de dominio otra vez, hay que actualizar esta linea
en el `.env` del servidor y correr `php artisan config:clear`.

## Paso 5 — CORS (`backend/config/cors.php`)

Laravel **no manda ningun header CORS por defecto** si no existe
`config/cors.php` — el middleware `HandleCors` revisa `config('cors.paths')`
y si esta vacio, no hace nada (se confirmo leyendo el codigo fuente del
paquete). El archivo vive en el repo y se actualiza como cualquier otro
cambio de codigo (commit + push + `git pull` en el servidor):

```php
'allowed_origins' => [
    env('FRONTEND_URL', 'http://localhost:3000'),
    'http://localhost:3000',
    'https://dfctalentohumano.fidelmercadotech.com',
    'https://solucionesrrhh.fidelmercadotech.com', // dominio de prueba anterior
],
'supports_credentials' => false, // el login usa tokens Bearer, no cookies
```

Cada vez que el frontend se despliega en un dominio nuevo, hay que agregar
ese dominio aqui tambien, o el navegador bloquea las peticiones aunque
`NEXT_PUBLIC_API_URL` este bien puesta.

## Paso 6 — Frontend (panel "Sitios web")

En el sitio del frontend en Hostinger, seccion "Variables de entorno":

```env
NEXT_PUBLIC_API_URL=https://api-rrhh.fidelmercadotech.com/api
```

**Critico:** Next.js "hornea" las variables `NEXT_PUBLIC_*` dentro del
codigo en el momento del build — no se leen en tiempo de ejecucion.
Guardar la variable no alcanza: hay que darle **"Guardar y reimplementar"**
para que tome efecto.

---

## Paso posterior al despliegue — Migrar de SQLite a MySQL

Esto **no fue parte del despliegue inicial**. El backend arranco en SQLite
(pasos 1-6, arriba) y funciono bien porque en ese momento solo habia datos
demo del seeder, nada real que perder. Cuando se decidio pasar a MySQL, se
hizo como una migracion aparte, semanas o dias despues, sin tocar el resto
de la configuracion ya funcionando.

Si en el futuro ya hay datos reales de produccion en SQLite, **este mismo
procedimiento no sirve tal cual** — hay que exportar/copiar filas antes de
cambiar `DB_CONNECTION`, no solo correr `migrate:fresh` (eso borra todo).

### 1. Crear la base MySQL

hPanel > Sitios web > `fidelmercadotech.com` > Bases de datos > "Crear
nueva base de datos MySQL y usuario". Guarda el nombre de base, usuario y
contraseña que te asigne (en Hostinger suelen quedar iguales, con el
prefijo `u<numero_de_cuenta>_`).

### 2. Cambiar el `.env` del servidor

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u910322706_rrhh
DB_USERNAME=u910322706_rrhh
DB_PASSWORD='la-contraseña-que-genero-hostinger'
```

(Nota la contraseña entre comillas simples en el `.env` — Laravel/phpdotenv
las interpreta bien, pero evita problemas si la contraseña trae caracteres
especiales como `&`.)

### 3. Bug real que aparecio al migrar (y que no era de esta base en particular)

`php artisan migrate` fallo con:

```text
SQLSTATE[HY000]: General error: 1005 Can't create table `employees`
(errno: 150 "Foreign key constraint is incorrectly formed")
```

Dos migraciones del proyecto declaraban una llave foranea hacia una tabla
que la migracion *siguiente* iba a crear (`employees.position_id ->
positions`, `shift_assignments.shift_id -> shifts`) — mismo timestamp de
archivo, y por orden alfabetico la tabla que referencia corria antes que la
tabla referenciada. **SQLite nunca lo valido** (no exige que la tabla
referenciada exista al crear la llave), por eso nadie lo noto hasta MySQL,
que si lo exige.

Se arreglo quitando esas dos lineas `->constrained()` de sus migraciones
originales y agregando una migracion nueva
(`2026_08_26_060000_add_deferred_foreign_keys.php`) que agrega esas dos
llaves foraneas al final, cuando ya existen todas las tablas. Esto ya esta
corregido en el repo — si vuelves a migrar desde cero, no deberia repetirse.

### 4. Migrar y sembrar en la base nueva

```bash
cd /home/u910322706/domains/fidelmercadotech.com/dashboard-RRHH/backend
php artisan config:clear
php artisan migrate:fresh --force
php artisan db:seed --force
```

`migrate:fresh` borra todo lo que hubiera en esa base antes de recrear —
seguro aqui porque era una base nueva y vacia. **No usar `migrate:fresh`
si ya hay datos reales que proteger.**

El `database/database.sqlite` viejo se deja intacto en el servidor, sin
usarse, como respaldo.

---

## Como actualizar el backend despues de un cambio de codigo

```bash
ssh -p 65002 -i ~/.ssh/hostinger_apirrhh u910322706@82.29.157.42
cd /home/u910322706/domains/fidelmercadotech.com/dashboard-RRHH
git pull origin master
cd backend
composer install --no-dev --optimize-autoloader --no-interaction   # solo si cambio composer.json
php artisan migrate --force                                        # solo si hay migraciones nuevas
php artisan config:clear
```

## Checklist rapido si el login vuelve a fallar

1. `curl -i https://<dominio-frontend>/api/auth/login -X POST ...` — si da
   **500 en texto plano** (no JSON), es el rewrite de `next.config.ts`
   intentando pegarle a `127.0.0.1:8001`: falta `NEXT_PUBLIC_API_URL` o
   falta redesplegar despues de ponerla.
2. Si da error de **CORS** en la consola del navegador: el dominio del
   frontend no esta en `backend/config/cors.php` -> agregarlo, commit,
   push, `git pull` en el servidor, `php artisan config:clear`.
3. Si da **401**: credenciales invalidas, no es un problema de conexion.
4. Si da **419**: no aplica a este proyecto (login usa tokens Bearer, no
   CSRF de sesion).
5. Si `php artisan migrate` falla con **errno 150** (MySQL): alguna
   migracion nueva probablemente declara una llave foranea antes de que
   exista la tabla referenciada — ver la seccion de MySQL arriba.
