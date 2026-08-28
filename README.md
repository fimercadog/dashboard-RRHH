# DFC Talento Humano — Plataforma de Recursos Humanos

Monorepo de un sistema HRMS (Human Resources Management System) para PYMES:
API REST en Laravel + panel privado y sitio público de marketing en Next.js.
Marca actual: **DFC Talento Humano**.

- `backend/` — Laravel 12 (PHP 8.2+), API REST, Sanctum, Spatie `laravel-permission`.
- `frontend/` — Next.js 16 (App Router, TypeScript, TailwindCSS v4), panel privado `/app` + sitio público de marketing.
- `docs/` — arquitectura, base de datos, API, roles/permisos, despliegue y estado de avance.

## Qué incluye

**Panel privado (`/app`)**

- Empleados, departamentos y cargos.
- Asistencia, vacaciones, permisos e incapacidades (con flujos de solicitud/aprobación).
- Documentos por empleado, turnos y asignación de turnos.
- Usuarios y roles (crear/editar/deshabilitar), con permisos vía Spatie `laravel-permission`.
- Auditoría de acciones (`audit-logs`).
- Dashboard con métricas, exportaciones CSV/PDF por módulo y tablas con búsqueda/paginación (TanStack Table).
- Asistente de IA (interfaz lista, sin proveedor conectado todavía).

**Sitio público de marketing** (`/`, `/producto`, `/soluciones`, `/precios`, `/reclutamiento`, `/nosotros`, `/blog`, `/contacto`, `/demo`)

- Landing multipágina estilo HRTech SaaS, con header/footer y componentes reutilizables.
- Hero animado (zoom de fondo + entrada en cascada) y animaciones de aparición al hacer scroll en el resto de secciones.
- Botón flotante de WhatsApp.
- SEO técnico (metadata, sitemap, robots, Open Graph).

Ver el detalle y lo que falta por hacer en [docs/development-status.md](docs/development-status.md).

## Requisitos

- PHP 8.2+ y Composer.
- Node.js 18+ y npm.
- SQLite (por defecto) o MySQL para el backend.

## Arranque local

```bash
# Backend (API)
cd backend
cp .env.example .env      # si no existe ya
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8001

# Frontend (panel + sitio publico), en otra terminal
cd frontend
cp .env.example .env.local   # opcional en local, ver Variables de entorno
npm install
npm run dev
```

- Sitio público: `http://localhost:3000`
- Panel privado: `http://localhost:3000/app/dashboard` (requiere login)
- API: `http://127.0.0.1:8001/api`

Si el puerto 3000 ya está ocupado, `next dev` toma automáticamente el
siguiente disponible (3001, etc.) — revisa el puerto real que imprime la
consola al arrancar.

Usuarios demo (uno por rol: Super Admin, Administrador de empresa, RRHH,
Supervisor, Empleado) en [docs/demo-users.md](docs/demo-users.md).

## Variables de entorno

**`backend/.env`**: `DB_CONNECTION` (`sqlite` en local; `mysql` en producción,
ver [docs/hostinger-deployment.md](docs/hostinger-deployment.md)),
`FRONTEND_URL` (para CORS, ver `config/cors.php`), `APP_URL`.

**`frontend/.env.local`**: `NEXT_PUBLIC_API_URL` — URL pública completa de la
API (incluyendo `/api`). En local puede omitirse: el frontend cae a la ruta
relativa `/api` vía el rewrite de `next.config.ts` hacia `127.0.0.1:8001`.
En cualquier despliegue donde backend y frontend NO comparten máquina, es
obligatoria (se hornea en build time, así que hay que redeployar tras
cambiarla).

## Documentación

| Doc | Contenido |
| --- | --- |
| [docs/architecture.md](docs/architecture.md) | Arquitectura general del sistema |
| [docs/database.md](docs/database.md) | Modelo de datos y migraciones |
| [docs/api.md](docs/api.md) | Endpoints de la API |
| [docs/modules.md](docs/modules.md) | Módulos del panel privado |
| [docs/roles-permissions.md](docs/roles-permissions.md) | Roles y permisos (Spatie) |
| [docs/demo-users.md](docs/demo-users.md) | Usuarios de prueba por rol |
| [docs/color-palette.md](docs/color-palette.md) | Paleta de marca DFC Talento Humano |
| [docs/hostinger-deployment.md](docs/hostinger-deployment.md) | Despliegue real en producción (Hostinger) |
| [docs/development-status.md](docs/development-status.md) | Qué está implementado, parcial y pendiente |

## Despliegue

El backend corre en producción en Hostinger (hPanel + SSH, MySQL) — ver el
runbook completo en [docs/hostinger-deployment.md](docs/hostinger-deployment.md).
El frontend se despliega como sitio Node.js aparte, apuntando a
`NEXT_PUBLIC_API_URL` de ese backend.

Existe además un repo hermano, [`demo-rrhh`](https://github.com/fimercadog/demo-rrhh),
con branding genérico y datos mock, pensado solo para mostrarle el producto a
clientes potenciales — no comparte dominio ni datos con este repo.

## Carrusel de Instagram

Imágenes del carrusel de presentación del producto en
[docs/carrusel-instagram/](docs/carrusel-instagram/) (`slide-01.png` … `slide-12.png`).
