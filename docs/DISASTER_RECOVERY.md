# Plan de Recuperación ante Desastres — DFC Talento Humano

**Última actualización:** 2026-10-22  
**Estado:** ACTIVO — backup script instalado, cron pendiente de activación manual en hPanel

---

## Arquitectura de datos

| Capa | Tecnología | Ubicación en servidor |
|------|-----------|----------------------|
| Base de datos | MariaDB 11.8 (MySQL) | `u910322706_rrhh` en `127.0.0.1:3306` |
| App backend (Laravel) | PHP 8.x | `/home/u910322706/domains/fidelmercadotech.com/dashboard-RRHH/backend/` |
| Frontend estático (Next.js) | HTML/JS/CSS | `/home/u910322706/domains/dfctalentohumano.fidelmercadotech.com/public_html/` |
| Backup diario | `.sql.gz` | `/home/u910322706/backups/` |

> **Nota técnica:** `dfctalentohumano-api.fidelmercadotech.com/public_html` es un symlink al directorio `public/` del backend (`dashboard-RRHH/backend/public`). El código de la aplicación vive bajo `fidelmercadotech.com/dashboard-RRHH/backend/`.

---

## Script de backup

Ubicación: `~/bin/backup_dfc.sh`  
Estado: **instalado y verificado** (ejecutado manualmente el 2026-10-22, produjo backup válido)

```bash
# Ejecución manual de prueba:
~/bin/backup_dfc.sh

# Verificar que el backup es válido:
ls -lh ~/backups/dfc_*.sql.gz | tail -3
zcat ~/backups/dfc_*.sql.gz | grep -c "CREATE TABLE"  # debe ser 30
```

El script:
- Hace `mysqldump --single-transaction` (sin bloqueo de tablas)
- Comprime con `gzip -9`
- Registra resultado en `~/logs/backup.log`
- Elimina backups con más de 30 días (rotación automática)

**Primer backup verificado:**
- Archivo: `dfc_u910322706_rrhh_20261022_201333.sql.gz`
- Tamaño: 14K comprimido
- Contenido: 30 tablas (`CREATE TABLE`), 25 bloques `INSERT INTO`, 1551 líneas SQL
- Tablas verificadas: `attendances`, `audit_logs`, `cache`, `companies`, `employees`, …

---

## ⚠️ Acción pendiente: Activar cron diario en hPanel

El hosting de Hostinger **no permite crear cron jobs por SSH** (el comando `crontab` está aliaseado a solo-lectura). El cron **debe configurarse desde el hPanel**.

### Pasos para activar el backup automático:

1. Ir a **hPanel → Hosting → Administrar** (cuenta `u910322706`)
2. Navegar a **Avanzado → Trabajos Cron**
3. Hacer clic en **Agregar trabajo cron**
4. Configurar:
   - **Expresión cron (5 campos):** `0 7 * * *`
   - **Comando:** `/home/u910322706/bin/backup_dfc.sh >> /home/u910322706/logs/backup.log 2>&1`
   - **Zona horaria del servidor:** UTC (Boston). `07:00 UTC = 02:00 COT` — madrugada, mínimo impacto.
5. Guardar y verificar que aparezca en la lista

> **Nota de zona horaria:** Hostinger US-Boston corre en UTC. Colombia es UTC-5. El objetivo es ejecutar a las 02:00 hora Colombia (COT), que equivale a **07:00 UTC**. El valor incorrecto sería 02:00 UTC (que correspondería a las 21:00 COT del día anterior).

**Verificación posterior:** Al día siguiente revisar `~/logs/backup.log` vía SSH:
```bash
tail -5 ~/logs/backup.log
```

---

## RPO y RTO

| Métrica | Valor objetivo | Estado actual |
|---------|---------------|--------------|
| **RPO** (pérdida máxima de datos) | 24 horas | ✅ Con cron diario activo |
| **RTO** (tiempo máximo de recuperación) | 2–4 horas | Manual (ver procedimiento abajo) |

---

## Procedimiento de restauración

> **NUNCA restaurar directamente sobre la BD de producción sin haber validado el dump primero.**

### Opción A — Restaurar sobre BD existente (último recurso, datos perdidos)

```bash
# 1. Identificar el backup más reciente
ls -lt ~/backups/dfc_*.sql.gz | head -3

# 2. Restaurar (DESTRUCTIVO — borra todos los datos actuales)
zcat ~/backups/dfc_u910322706_rrhh_TIMESTAMP.sql.gz \
  | mysql -h127.0.0.1 -uu910322706_rrhh -p'CONTRASEÑA' u910322706_rrhh
```

### Opción B — Restaurar a BD temporal y validar primero (RECOMENDADO)

1. En **hPanel → Bases de datos MySQL**, crear `u910322706_restore_test`
2. Añadir al usuario `u910322706_rrhh` con todos los privilegios sobre esa BD
3. Restaurar:
   ```bash
   zcat ~/backups/dfc_*.sql.gz | head -1  # Verificar cabecera
   zcat ~/backups/dfc_u910322706_rrhh_TIMESTAMP.sql.gz \
     | mysql -h127.0.0.1 -uu910322706_rrhh -p'CONTRASEÑA' u910322706_restore_test
   ```
4. Validar: `mysql ... -e "SHOW TABLES; SELECT COUNT(*) FROM employees;"`
5. Si todo está bien, renombrar tablas o migrar datos a la BD de producción
6. Eliminar `u910322706_restore_test` desde hPanel

### Nota sobre contraseñas

Las credenciales de BD están en `~/domains/fidelmercadotech.com/dashboard-RRHH/backend/.env`. La contraseña contiene `!` — usar comillas simples en MySQL CLI o archivo `~/.my.cnf`.

---

## Limitación conocida: restauración automatizada

El usuario de BD `u910322706_rrhh` **no tiene privilegio `CREATE DATABASE`** (restricción de Hostinger shared hosting). Por eso la prueba de restauración a `dfc_restore_test` no pudo automatizarse. La creación de la BD de prueba requiere el panel hPanel.

**Impacto:** El proceso de restore no es 100% automatizable. El backup en sí es completamente válido y funcional.

---

## Monitoreo

Revisar periódicamente el log de backups:

```bash
# Ver últimos 10 registros
tail -20 ~/logs/backup.log

# Verificar que hay un backup de las últimas 25 horas
find ~/backups -name "dfc_*.sql.gz" -mtime -1 | wc -l
# Debe devolver >= 1
```

---

## Inventario de archivos críticos en producción

| Archivo | Descripción |
|---------|-------------|
| `~/domains/fidelmercadotech.com/dashboard-RRHH/backend/.env` | Config de producción (BD, app key, Sanctum) |
| `~/domains/fidelmercadotech.com/dashboard-RRHH/backend/database/database.sqlite` | SQLite residual de dev — ignorar en producción |
| `~/bin/backup_dfc.sh` | Script de backup (no versionar — contiene credenciales) |
| `~/backups/dfc_*.sql.gz` | Backups diarios (30 días de retención) |
| `~/logs/backup.log` | Log de ejecuciones del cron |

---

## Frontend (estático)

El frontend no tiene base de datos propia. Es un export estático de Next.js (`output: "export"`). Para restaurarlo:

```bash
# Desde la máquina local, re-desplegar con el script SFTP:
python C:\Users\fidel\AppData\Local\Temp\deploy_frontend.py
```

El código fuente está en Git — cualquier commit es un punto de restauración del frontend.
