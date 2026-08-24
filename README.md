# FidelOS HRMS

Monorepo para la plataforma administrativa privada de Recursos Humanos.

- `backend/`: Laravel 12 API REST con SQLite inicial.
- `frontend/`: Next.js 16 App Router, TypeScript, TailwindCSS, shadcn-inspired UI y TanStack Table.
- `docs/`: arquitectura, base de datos, API, permisos y estado de avance.

## Entorno local

El objetivo del proyecto es PHP 8.3+, pero esta maquina tiene PHP 8.2.12. Composer instalo Laravel 12 compatible con el entorno local.

```bash
cd backend
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8001

cd ../frontend
npm run dev
```

Frontend: `http://localhost:3000/app/dashboard`
API: `http://127.0.0.1:8001/api`
