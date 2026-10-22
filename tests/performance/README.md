# Performance Tests — DFC Talento Humano

Suite de pruebas de rendimiento con [k6](https://k6.io/) para el backend Laravel.

---

## Instalación de k6 (Windows)

winget no siempre encuentra el paquete con `--id k6.k6`. Opciones:

**Opción A — winget (buscar el ID exacto primero):**
```powershell
winget search k6
winget install -e --id <ID-que-salga>
```

**Opción B — descarga directa (más confiable):**
1. Ve a https://github.com/grafana/k6/releases/latest
2. Descarga `k6-vX.X.X-windows-amd64.msi`
3. Ejecuta el instalador
4. Reinicia la terminal

**Verificar instalación:**
```powershell
k6 version
```

---

## Variables de entorno

Copia `.env.example` a `.env` en esta carpeta y completa los valores:

```
BASE_URL=http://127.0.0.1:8001
K6_USER=admin@andespeople.co
K6_PASS=password
```

> **Nunca commitear `.env` con credenciales reales.** El `.gitignore` de esta
> carpeta ya lo excluye.

El usuario `admin@andespeople.co` (creado por el seeder) tiene los permisos
necesarios para todos los endpoints cubiertos. En staging usar credenciales
separadas generadas con `php artisan tinker`.

---

## Comandos de ejecución

### Desde la raíz del proyecto

```bash
npm run perf:smoke    # 1 VU, 30 s  — verificar que la API responde
npm run perf:load     # 10 VUs, 2 min — carga normal
npm run perf:medium   # 50 VUs, 5 min — carga sostenida
npm run perf:high     # 100 VUs, 9 min — ⚠️ solo en local/staging
```

### Directamente con k6 (pasando credenciales)

```bash
k6 run \
  --env BASE_URL=http://127.0.0.1:8001 \
  --env K6_USER=admin@andespeople.co \
  --env K6_PASS=password \
  tests/performance/scenarios/smoke.js
```

### Desde el directorio frontend/

```bash
npm run perf:smoke    # paths ya corregidos con ../tests/...
```

---

## Escenarios

| Escenario    | VUs | Duración        | Propósito                                    |
|-------------|-----|-----------------|----------------------------------------------|
| smoke       | 1   | 30 s            | Humo: confirmar que los endpoints responden   |
| load-low    | 10  | 2 min           | Carga normal esperada en producción           |
| load-medium | 50  | 5 min (+ rampas)| Carga sostenida / pico moderado              |
| load-high   | 100 | 9 min (+ rampas)| ⚠️ Solo tras evaluar los anteriores          |

---

## Endpoints cubiertos

| Endpoint                              | Permiso requerido    |
|---------------------------------------|----------------------|
| `GET /api/employees`                  | employees.manage     |
| `GET /api/employees/stats`            | employees.manage     |
| `GET /api/attendances`                | attendance.manage    |
| `GET /api/attendance-stats`           | attendance.manage    |
| `GET /api/dashboard`                  | dashboard.view       |
| `GET /api/clients`                    | clients.manage       |
| `GET /api/products`                   | inventory.manage     |
| `GET /api/financial-transactions`     | finance.view         |
| `GET /api/vacation-requests`          | requests.approve     |
| `GET /api/roles`                      | roles.manage         |
| `POST /api/auth/login`                | público              |

---

## Thresholds

| Métrica                       | Smoke/Load-Low | Load-Medium | Load-High        |
|-------------------------------|----------------|-------------|------------------|
| `http_req_duration p(95)`     | < 800 ms       | < 1 000 ms  | < 3 000 ms *     |
| `http_req_duration p(90)`     | —              | < 800 ms    | —                |
| `http_req_failed`             | < 1 %          | < 1 %       | < 5 % *          |
| `dur_employees p(95)`         | —              | < 1 000 ms  | < 3 000 ms *     |

\* Umbral provisional — ajustar con evidencia real tras primera ejecución.

---

## Aislamiento multiempresa

El `company_id` se extrae automáticamente del token Sanctum por
`BaseCrudController`. No se pasa en el payload ni en la URL — sería
ignorado de todas formas. Cada VU autentica con el mismo usuario de prueba
y solo ve datos de su empresa. El aislamiento multiempresa es estructural,
no depende de los scripts k6.

---

## Resultados

| Escenario | Fecha | Entorno | avg | p90 | p95 | max | Error rate | Checks | Notas |
|-----------|-------|---------|-----|-----|-----|-----|------------|--------|-------|
| smoke | 2026-10-22 | local (SQLite, `php artisan serve`) | 362 ms | 382 ms | **418 ms** | 943 ms | 0.00% | **60/60 ✓** | 10 endpoints, 1 VU, 30 s |
| load-low | 2026-10-22 | local (SQLite, `php artisan serve`) | 2 760 ms | 2 990 ms | **3 070 ms** | 3 830 ms | 0.00% | **400/400 ✓** | 10 endpoints, 10 VUs, 2 min |
| smoke | 2026-10-22 | **PROD** (Hostinger LiteSpeed, PHP 8.4 LSAPI, MySQL, hcdn CDN) | 191 ms | 236 ms | **261 ms** | 610 ms | **19.35%** ✗ | **54/60** (90%) | 8/10 endpoints OK; 2 fallos — ver análisis abajo |
| load-low | 2026-10-22 | PROD | — | — | — | — | — | — | **NO ejecutado** — smoke bloqueado por error rate >1% |

### Análisis smoke PROD — 2026-10-22

**Latencia excelente (p95 = 261 ms)** en los 8 endpoints que sí respondieron — 37% más rápido que local
(p95 local = 418 ms). Hostinger LiteSpeed + PHP 8.4 LSAPI + MySQL real vs SQLite + `php artisan serve`.

**2 endpoints bloqueados en producción:**

| Endpoint | Código | Causa |
|----------|--------|-------|
| `GET /api/employees/stats` | 401 | El usuario `admin@andespeople.co` en la BD de producción no tiene el permiso `employees.manage`. Probable causa: seeder no se ejecutó post-deploy, o el role de producción tiene permisos distintos. |
| `GET /api/attendance-stats` | 404 | La ruta no existe en el servidor de producción. El código está desplegado (`api.php` la registra en línea 127) pero puede haber un problema de caché de rutas (`php artisan route:cache` no ejecutado tras el último deploy, o un deploy parcial). |

**Ninguno es un problema de rendimiento.** Son problemas de estado del entorno de producción.

**Acciones correctivas recomendadas (fuera del alcance de este test):**

1. SSH → `php artisan route:cache` (o `php artisan route:clear`) para forzar recarga de rutas.
2. Verificar que el usuario de producción tenga el permiso `employees.manage` (`php artisan tinker` → `User::find(1)->hasPermissionTo('employees.manage')`).
3. Si la BD de producción es nueva/limpia: ejecutar el seeder o asignar permisos manualmente.

Una vez corregidos, re-ejecutar smoke para confirmar 60/60 checks y habilitar load-low.

---

### Análisis load-low — cuello de botella identificado

`php artisan serve` es un servidor de desarrollo **single-threaded**: atiende
una request a la vez. Con 10 VUs concurrentes, cada request espera en cola a
las anteriores, produciendo latencias ~7× mayores que con 1 VU:

- 1 VU → avg 362 ms — tiempo real de la app
- 10 VUs → avg 2 760 ms — cola del servidor de desarrollo

**La app no tiene errores** (400/400 checks, 0% HTTP failures). La degradación
es lineal y es un artefacto del servidor de desarrollo, no del código.

> **Para una medición válida bajo concurrencia** se necesita nginx + PHP-FPM
> (multi-process) en staging o producción. `php artisan serve` no es un proxy
> válido de rendimiento bajo carga concurrente.

### Próximo paso para validar rendimiento real

Ejecutar contra el entorno de Hostinger (producción) con autorización expresa, o
levantar un entorno local con XAMPP/Laragon (Apache + PHP-FPM). Los thresholds
de latencia del README son objetivos de producción, no de dev server.

---

## ADVERTENCIA

> **NO ejecutar `load-medium`, `load-high` ni escenarios de stress contra producción.**
> Usar exclusivamente contra entornos locales o de staging.
> Ejecutar siempre con las credenciales de una cuenta de prueba, nunca con
> cuentas reales de clientes.
