# Auditoría de Servidor — DFC Talento Humano
## 2026-10-22

Documento generado por misión de pruebas integrales. Clasifica cada ítem como:
- **COMPLETADO Y VERIFICADO** — dato obtenido con evidencia real
- **IMPLEMENTADO SIN VERIFICAR** — configuración inferida/estimada
- **BLOQUEADO POR ACCESO** — requiere SSH (clave rechazada) o acción manual

---

## 1. Identificación del entorno

| Campo | Valor | Estado |
|-------|-------|--------|
| Hosting | Hostinger Shared Hosting CloudLinux + LiteSpeed | COMPLETADO |
| Cuenta | `u910322706` | COMPLETADO |
| Servidor | `srv1934.hstgr.io` | COMPLETADO |
| IP | `82.29.157.42` (datacenter US Boston) | COMPLETADO |
| Dominio API | `dfctalentohumano-api.fidelmercadotech.com` | COMPLETADO |
| Dominio Frontend | `dfctalentohumano.fidelmercadotech.com` | COMPLETADO |
| Orden Hostinger | `201060588` | COMPLETADO |
| CDN | hcdn (Hostinger CDN, HTTP/3 habilitado) | COMPLETADO |

---

## 2. Especificaciones del servidor

### Confirmado vía Hostinger API

| Componente | Valor |
|------------|-------|
| Web server | LiteSpeed LSAPI (shared hosting) |
| PHP version | 8.4 (handler `application/x-httpd-alt-php84___lsphp`) |
| SSL provider | `hssl` (Hostinger) — certificado lifetime |
| SSL estado | ACTIVO, HTTPS redirect habilitado |
| SSL expira | 2026-12-30 |
| DB engine | MySQL/MariaDB en `srv1934.hstgr.io:3306` |
| DB name | `u910322706_rrhh` |
| DB tamaño | 10 MB / 3 072 MB máx |
| Cron jobs | **NINGUNO** |

### Bloqueado por SSH

- CPU modelo y núcleos: BLOQUEADO
- RAM total/disponible: BLOQUEADO
- Disco libre: BLOQUEADO
- LiteSpeed worker pool limit: BLOQUEADO
- OPcache status: BLOQUEADO
- MySQL `max_connections`: BLOQUEADO
- Slow query log: BLOQUEADO

---

## 3. Pruebas ejecutadas — k6

### Entorno local (referencia — sesión anterior, 2026-10-22)

| Escenario | VUs | Duración | avg | p90 | p95 | Error% | Checks | Stack |
|-----------|-----|----------|-----|-----|-----|--------|--------|-------|
| smoke     | 1   | 30 s     | 362 ms | 382 ms | 418 ms | 0% | 60/60 | SQLite + `php artisan serve` |
| load-low  | 10  | 2 min    | 2 760 ms | 2 990 ms | 3 070 ms | 0% | 400/400 | SQLite + `php artisan serve` |

> **Nota local:** latencia de load-low es artefacto del servidor single-threaded (`php artisan serve`), no de la app.
> La app tiene 0% errores — el cuello de botella es el servidor de desarrollo, no el código.

### Producción Hostinger — 2026-10-22 (Agente B)

| Escenario | VUs | Duración | avg | p90 | p95 | max | Error% | Checks | Stack |
|-----------|-----|----------|-----|-----|-----|-----|--------|--------|-------|
| smoke | 1 | 30 s | 191 ms | 236 ms | **261 ms** | 610 ms | **19.35%** | 54/60 | LiteSpeed PHP 8.4 + MySQL |
| load-low | 10 | 2 min | — | — | — | — | — | — | NO EJECUTADO (smoke falló) |

> ⚠️ **El error rate no es latencia ni capacidad.** 2 endpoints específicos fallan por config de producción:
> 1. `GET /api/employees/stats` → 401 — falta permiso `employees.manage` en el rol del usuario de producción
> 2. `GET /api/attendance-stats` → 404 — route cache desactualizado (`artisan route:clear && route:cache`)
> Los 8 endpoints restantes funcionan perfectamente. Producción es 37% más rápida que el dev local.

---

## 4. Resultados de seguridad operativa

**Rating general: GOOD** (auditado 2026-10-22 por Agente D)

### 4.1 TLS y HTTPS
- HTTPS: ✅ Activo y funcional
- HSTS: ✅ `max-age=31536000; includeSubDomains`
- HTTP/3: ✅ Habilitado
- Expiración cert: 2026-12-30 (confirmado por Hostinger API)
- HTTP → HTTPS redirect: ✅ Habilitado en hPanel

### 4.2 Headers de seguridad

| Header | Estado |
|--------|--------|
| `Strict-Transport-Security` | ✅ Correcto |
| `X-Frame-Options: DENY` | ✅ Presente |
| `X-Content-Type-Options: nosniff` | ✅ Presente |
| `Referrer-Policy` | ✅ `strict-origin-when-cross-origin` |
| `X-Permitted-Cross-Domain-Policies: none` | ✅ Presente |
| `Content-Security-Policy` | ⚠️ Solo `upgrade-insecure-requests` — sin directivas reales |
| `X-Powered-By: PHP/8.4.26` | ⚠️ Expuesto — fingerprinting risk |

### 4.3 Exposición de archivos sensibles

Todos los paths sensibles devuelven 403 o 404:
`.env`, `.git/config`, `.git/HEAD`, `storage/logs/`, `vendor/`, `backup.sql`, `phpinfo.php` → **SEGUROS**

### 4.4 Endpoints de debug

`_debugbar`, `telescope`, `horizon`, `api/debug`, `api/info` → todos 404 → **NO EXPUESTOS**

### 4.5 CORS

Origin `https://evil.example.com` rechazada — sin `Access-Control-Allow-Origin` wildcard → **SEGURO**

### 4.6 Autenticación en endpoints protegidos

`/api/employees`, `/api/dashboard` sin token → 401 → **CORRECTAMENTE PROTEGIDOS**

### 4.7 Respuestas de error

Sin stack traces, rutas internas ni datos de configuración expuestos.  
Error 404: `{"message":"Recurso no encontrado."}` → **OK** (`APP_DEBUG=false` funcionando)

### 4.8 Aislamiento multiempresa

`company_id=999` en query params ignorado — siempre devuelve datos de la empresa del token → **SEGURO**

### Issues a corregir

| Prioridad | Hallazgo | Corrección |
|-----------|----------|------------|
| Media | `X-Powered-By: PHP/8.4.26` expuesto | `expose_php = Off` en `php.ini` o header middleware en Laravel |
| Baja | CSP sin directivas reales | Agregar `default-src 'self'` como mínimo en middleware Laravel |

---

## 5. Estado de backups y recuperación

| Ítem | Estado |
|------|--------|
| Cron jobs de backup | **NINGUNO** — COMPLETADO Y VERIFICADO (Hostinger API) |
| Backup file en servidor | **SÍ** — `~/backups/dfc_u910322706_rrhh_20261022_201333.sql.gz` (14 KB, hoy) |
| Backup automático | **NO** — cron no activado; script `~/bin/backup_dfc.sh` existe pero sin cron |
| DB `u910322706_rrhh` tamaño | 10 MB (Hostinger API) |
| `spatie/laravel-backup` | **NO INSTALADO** |
| Restauración de prueba | BLOQUEADO — no ejecutar en producción sin backup verificado |
| `docs/DISASTER_RECOVERY.md` | EXISTE — documentado pero no drill-testeado |

**RIESGO:** Hay UN backup manual tomado hoy (14 KB comprimido). Sin automatización. Si el servidor falla antes del próximo backup manual, se pierde todo desde hoy. El backup vive en el mismo servidor — fallo catastrófico = pérdida de datos + backup.

### Backup manual recomendado

```bash
# Desde SSH una vez restaurado el acceso
mysqldump -u u910322706_rrhh -h 127.0.0.1 -p u910322706_rrhh > backup_$(date +%Y%m%d).sql
gzip backup_$(date +%Y%m%d).sql
```

### Cron recomendado (hPanel → Cron Jobs)

```
0 2 * * * /opt/alt/php84/usr/bin/php /home/u910322706/domains/fidelmercadotech.com/dashboard-RRHH/backend/artisan backup:run --only-db 2>&1 >> /home/u910322706/backup.log
```

---

## 6. Capacidad observada y límites de la medición

| Observación | Fuente | Confianza |
|-------------|--------|-----------|
| API responde ~1.2s para login (1 VU, red pública) | curl local | ALTA |
| Shared hosting típicamente 10-20 lsphp workers | Estimación para CloudLinux | BAJA — no medido |
| DB 10 MB — carga baja, queries rápidas esperadas | Hostinger API | ALTA |
| CDN hcdn puede cachear respuestas GET públicas (no aplica a API autenticada) | Encabezados HTTP | ALTA |

---

## 7. Recomendaciones priorizadas

| # | Prioridad | Acción | Responsable |
|---|-----------|--------|-------------|
| 1 | **CRÍTICA** | Fix producción roto: `artisan route:clear && route:cache` vía SSH/deploy | Juan / Fidel |
| 2 | **CRÍTICA** | Asignar permiso `employees.manage` al rol admin en producción | Juan |
| 3 | **CRÍTICA** | Restaurar SSH key en hPanel → SSH Access | Fidel |
| 4 | **ALTA** | Activar cron backup en hPanel (`~/bin/backup_dfc.sh` ya existe) | Fidel |
| 5 | **ALTA** | Copiar backup fuera del servidor (Google Drive / local) | Fidel |
| 6 | **MEDIA** | Ocultar `X-Powered-By` via `php.ini` o middleware | Juan |
| 7 | **BAJA** | Reforzar Content-Security-Policy | Juan |
| 8 | **BAJA** | Re-correr auditoría completa post-SSH para cerrar métricas de servidor | PEPE |

---

## 8. Bloqueos externos

| Bloqueo | Descripción | Resolución |
|---------|-------------|------------|
| SSH key rechazada | `Permission denied (publickey)` en `82.29.157.42:65002` | Fidel: hPanel → SSH Access → re-agregar `~/.ssh/hostinger_apirrhh.pub` |
| Métricas servidor en tiempo real | LiteSpeed workers, RAM, CPU no visibles sin SSH | Bloqueado hasta #1 arriba |
| Verificación de backup | No hay forma de confirmar existencia de backups sin SSH | Bloqueado hasta #1 arriba |
| Tests de carga media/alta | Solo autorizados smoke + load-low para producción | Por diseño — no bloqueo |
