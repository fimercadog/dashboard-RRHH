# SPEC-CONT-002 — Protocolo de Continuidad, Backup y Pruebas

> Estado: BORRADOR — pendiente de aprobación  
> Autor: PEPE  
> Fecha: 2026-10-21  
> Cubre: R1 (backup real), R2 (restauración), R3 (MySQL), R4 (protocolo completo)

---

## 1. Contexto

El proyecto corre en Hostinger (shared hosting, MySQL 8.x, PHP 8.4).  
La suite de pruebas usa SQLite `:memory:` — no hay pruebas contra el motor real.  
El script de deploy ejecuta `migrate --force` con un guard de backup (implementado,
**pendiente de verificación real** en producción).

---

## 2. R4 — Protocolo de continuidad por tarea

### 2.1 Antes de implementar

| Paso | Responsable | Qué hace |
|------|------------|----------|
| Leer `docs/CORE_CONTRACT.md` | PEPE | Verificar que la tarea no toca contratos congelados |
| `git status` limpio | PEPE | No empezar con cambios flotantes |
| Suite pasa en verde | PEPE→JUAN | `php artisan test` debe pasar antes de tocar código |
| Identificar módulos afectados | SPEC-PLANNER | Listar archivos, rutas, tablas, jobs |

### 2.2 Durante la implementación

- JUAN ejecuta `php artisan test` al terminar cada unidad lógica.
- Si una prueba existente rompe → parar, no seguir hasta resolver.
- Cambios de BD siempre en migraciones nuevas; nunca editar migraciones ya publicadas.

### 2.3 Rollback de código

**Escenario A — código no publicado (solo local):**
```bash
git revert <hash> --no-commit   # revierte cambios, deja staging para revisar
git commit -m "revert: <motivo>"
```

**Escenario B — código ya en GitHub, no desplegado:**
```bash
git revert <hash>
git push origin master
```
No usar `git reset --hard` en una rama pública.

**Escenario C — código desplegado en producción:**
1. Hacer `git revert` + push (igual que B).
2. Ejecutar el deploy (que corre el nuevo código + `migrate --force`).
3. Si la migración revertida necesita deshacer columnas/tablas → escribir
   una migración `down` explícita antes de desplegar.

### 2.4 Recuperación de base de datos

**Ante una migración fallida en producción:**
1. El guard de backup generó `~/backups/deploy_YYYYMMDD_HHMMSS.sql` antes del migrate.
2. Conectar por SSH al servidor.
3. Restaurar:
   ```bash
   MYSQL_PWD="$DB_PASS" mysql -u "$DB_USER" "$DB_NAME" < ~/backups/deploy_YYYYMMDD_HHMMSS.sql
   ```
4. Verificar con `SHOW TABLES` y conteo de registros clave.
5. Revertir el código (Escenario C arriba).
6. Volver a desplegar con la versión revertida.

> **Regla:** nunca restaurar sobre producción sin haber probado el .sql en una BD separada primero (ver R2).

### 2.5 Backups manuales adicionales

El backup automático ocurre en cada deploy (guard en `deploy-hostinger.md`).
Backup manual antes de un cambio de esquema grande:
```bash
MYSQL_PWD="$DB_PASS" mysqldump -u "$DB_USER" "$DB_NAME" \
  > ~/backups/manual_$(date +%Y%m%d_%H%M%S).sql
```
Retención sugerida: últimos 7 backups (limpiar manualmente si superan 5 GB).

### 2.6 Pérdida de internet / caída de Claude Code

**Si Claude Code cae en mitad de una sesión:**
1. Abrir nueva sesión. Claude recupera contexto desde el último resumen de sesión.
2. Verificar `git status` — si hay cambios sin commit, revisarlos y decidir.
3. Leer `docs/PENDIENTES.md` para retomar desde el punto correcto.
4. Nunca asumir que una tarea quedó terminada por el historial del chat.

**Si se pierde internet durante un deploy activo:**
- El script paramiko perderá la conexión SSH. El servidor puede quedar en estado intermedio.
- Al recuperar internet: SSH manual al servidor y verificar:
  ```bash
  php artisan migrate:status   # ver qué migraciones corrieron
  php artisan config:cache     # re-cachear si quedó sin caché
  ```
- Si hay migraciones a medias → restaurar desde el último backup.

### 2.7 Informe de continuidad por tarea

Al cerrar cada tarea de desarrollo, PEPE registra en `PENDIENTES.md`:
- Commits generados (hash + descripción).
- Pruebas ejecutadas y resultado.
- Estado del deploy (si aplica).
- Riesgos abiertos.
- Próximo paso.

### 2.8 Post-deploy — verificaciones mínimas

Después de cada deploy exitoso (`DONE` en la salida):
1. `curl -s https://dfctalentohumano-api.fidelmercadotech.com/api/ping` → debe responder 200.
2. Abrir `https://dfctalentohumano.fidelmercadotech.com` → login funciona.
3. Verificar el módulo afectado por el deploy (smoke test manual, 2-3 acciones clave).

### 2.9 Responsabilidades de agentes

| Agente | Responsabilidad de continuidad |
|--------|-------------------------------|
| PEPE | Verificar `git status` limpio antes/después; leer PENDIENTES; no declarar LISTO sin evidencia real |
| JUAN | Ejecutar `php artisan test` antes de entregar; no dejar migraciones sin prueba |
| CENTINELA | Detectar si cambios de BD rompen aislamiento multi-empresa |
| NANCY | Verificar que los criterios de aceptación incluyen pruebas ejecutadas, no solo "parece funcionar" |
| MARGARITA | Ejecutar smoke test post-deploy cuando hay cambio en producción |

### 2.10 Criterios de aceptación (R4)

- [ ] `docs/PENDIENTES.md` actualizado al cierre de cada sesión
- [ ] Procedimiento de rollback probado (ver R1/R2)
- [ ] Script de deploy ejecutado al menos una vez con el guard activo y backup verificado
- [ ] `docs/DEVELOPMENT_WORKFLOW.md` actualizado con el checklist de continuidad

---

## 3. R1 — Verificación del backup en Hostinger

### 3.1 Prerrequisitos (verificar antes de ejecutar)

| Requisito | Cómo verificar | Estado |
|-----------|---------------|--------|
| `~/backups/` existe o se puede crear | `mkdir -p ~/backups` vía SSH | No verificado |
| `mysqldump` disponible en PATH | `which mysqldump` vía SSH | No verificado |
| Espacio libre suficiente (≥ 500 MB) | `df -h ~` vía SSH | No verificado |
| Credenciales de BD en `.env` correctas | Script lee el `.env` del servidor | No verificado |

### 3.2 Procedimiento seguro (solo el backup, sin migrate)

Ejecutar únicamente la sección de backup del script de deploy — sin git pull, sin composer, sin migrate:

```python
# Script aislado — SOLO el backup, sin migrate ni caché
# Credenciales vienen de docs/deploy-hostinger.md (gitignored)

REMOTE = "..."  # ruta al backend en el servidor

VERIFY_CMD = "\n".join([
    "BACKUP_DIR=/home/u910322706/backups && mkdir -p $BACKUP_DIR",
    f"DB_NAME=$(grep '^DB_DATABASE=' {REMOTE}/.env | cut -d= -f2 | tr -d '\\r')",
    f"DB_USER=$(grep '^DB_USERNAME=' {REMOTE}/.env | cut -d= -f2 | tr -d '\\r')",
    f"DB_PASS=$(grep '^DB_PASSWORD=' {REMOTE}/.env | cut -d= -f2 | tr -d '\\r')",
    "BACKUP_FILE=$BACKUP_DIR/verify_$(date +%Y%m%d_%H%M%S).sql",
    "BACKUP_TMP=${BACKUP_FILE}.tmp",
    "export MYSQL_PWD=$DB_PASS",
    "mysqldump -u $DB_USER $DB_NAME > $BACKUP_TMP 2>/tmp/dump_err_verify.txt",
    "DUMP_RC=$?; unset MYSQL_PWD",
    "if [ $DUMP_RC -ne 0 ]; then echo VERIFY_FAILED:dump_exit=$DUMP_RC; exit 1; fi",
    "BYTES=$(wc -c < $BACKUP_TMP | tr -d ' ')",
    "if [ ${BYTES:-0} -lt 2000 ]; then echo VERIFY_FAILED:size_too_small=${BYTES}b; exit 1; fi",
    "mv $BACKUP_TMP $BACKUP_FILE",
    "echo VERIFY_OK:$(basename $BACKUP_FILE):${BYTES}bytes",
    "head -5 $BACKUP_FILE",   # encabezado del SQL
    "df -h /home/u910322706 | tail -1",
    "ls -lh $BACKUP_DIR/ | tail -5",
])
# Imprimir resultado filtrando líneas con credenciales
safe_out = "\n".join(l for l in out.splitlines()
                     if not any(k in l for k in ("DB_PASS=","MYSQL_PWD=")))
```

### 3.3 Criterios de aceptación (R1)

- [ ] `VERIFY_OK:verify_YYYYMMDD_HHMMSS.sql:NNNNNbytes` en la salida
- [ ] Bytes ≥ 50,000 (ajustar al crecer la BD)
- [ ] `head -5` muestra `-- MySQL dump` o `-- MariaDB dump`
- [ ] `df -h` muestra al menos 500 MB libres después del backup
- [ ] Archivo existe en `~/backups/` del servidor

---

## 4. R2 — Restauración en base de datos separada

### 4.1 Prerrequisitos

| Requisito | Cómo resolver |
|-----------|--------------|
| Segunda BD en el servidor | Crear `u910322706_dfc_restore` vía panel Hostinger o `CREATE DATABASE` por SSH |
| Usuario con acceso a esa BD | Mismo usuario de BD (ya tiene permisos en el servidor) |
| Backup generado (R1 completo) | Usar el archivo `verify_YYYYMMDD_HHMMSS.sql` de R1 |

### 4.2 Procedimiento

```bash
# 1. Crear BD de prueba (vía SSH — NO es la BD de producción)
MYSQL_PWD="$DB_PASS" mysql -u "$DB_USER" \
  -e "CREATE DATABASE IF NOT EXISTS u910322706_dfc_restore;"

# 2. Restaurar
MYSQL_PWD="$DB_PASS" mysql -u "$DB_USER" u910322706_dfc_restore \
  < ~/backups/verify_YYYYMMDD_HHMMSS.sql

# 3. Verificar tablas y registros clave
MYSQL_PWD="$DB_PASS" mysql -u "$DB_USER" u910322706_dfc_restore -e "
  SHOW TABLES;
  SELECT 'employees' AS tbl, COUNT(*) AS n FROM employees;
  SELECT 'companies' AS tbl, COUNT(*) AS n FROM companies;
  SELECT 'users' AS tbl, COUNT(*) AS n FROM users;
"

# 4. Limpiar BD de prueba
MYSQL_PWD="$DB_PASS" mysql -u "$DB_USER" \
  -e "DROP DATABASE u910322706_dfc_restore;"
```

### 4.3 Criterios de aceptación (R2)

- [ ] `SHOW TABLES` devuelve al menos: `employees`, `companies`, `users`, `attendances`
- [ ] `COUNT(*)` en `employees` y `companies` coincide con producción
- [ ] Sin líneas `ERROR` en la salida de mysql
- [ ] BD de prueba eliminada al terminar

---

## 5. R3 — Suite de pruebas contra MySQL

### 5.1 Por qué SQLite no es suficiente

| Diferencia | Impacto |
|-----------|---------|
| `ONLY_FULL_GROUP_BY` (MySQL strict mode) | Queries con GROUP BY sin agregación fallan en MySQL, pasan en SQLite |
| Foreign keys (inmediatas en MySQL) | Inserciones en orden incorrecto pasan en SQLite, rompen en MySQL |
| ENUM vs string | SQLite acepta cualquier valor, MySQL rechaza valores fuera del enum |
| JSON columns | Comportamiento de índices JSON diferente |
| Timestamps y timezone | Diferencias en CURRENT_TIMESTAMP |

### 5.2 Archivo `phpunit.mysql.xml` (nuevo, gitignored)

```xml
<!-- phpunit.mysql.xml — NO commitear, contiene credenciales locales -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="MySQL-Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include><directory>app</directory></include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="mysql"/>
        <env name="DB_HOST" value="127.0.0.1"/>
        <env name="DB_PORT" value="3306"/>
        <env name="DB_DATABASE" value="dfc_test"/>     <!-- BD local de pruebas, NO producción -->
        <env name="DB_USERNAME" value="root"/>         <!-- ajustar a credenciales locales -->
        <env name="DB_PASSWORD" value=""/>             <!-- ajustar -->
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_STORE" value="array"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
    </php>
</phpunit>
```

Agregar a `.gitignore`:
```
phpunit.mysql.xml
```

Ejecutar:
```bash
php artisan test --configuration=phpunit.mysql.xml
```

### 5.3 Guardia anti-producción en `TestCase.php`

```php
// tests/TestCase.php — agregar en setUp() antes de parent::setUp()
protected function setUp(): void
{
    parent::setUp();
    $dbName = config('database.connections.' . config('database.default') . '.database');
    if (str_contains(strtolower((string) $dbName), 'prod')) {
        $this->markTestSkipped('Bloqueado: parece apuntar a producción (' . $dbName . ')');
    }
}
```

### 5.4 Criterios de aceptación (R3)

- [ ] `phpunit.mysql.xml` creado localmente y en `.gitignore`
- [ ] `php artisan test --configuration=phpunit.mysql.xml` pasa las mismas pruebas Feature que SQLite
- [ ] Guardia anti-producción commitada en `tests/TestCase.php`
- [ ] Documentado en `docs/DEVELOPMENT_WORKFLOW.md`: "antes de release, correr también suite MySQL"

---

## 6. Dependencias y orden de ejecución

```
R1 (backup real)
  ↓
R2 (restauración en BD separada)   ← R1 debe pasar primero
  ↓
Declarar CRÍTICO resuelto

R3 (suite MySQL)                   ← independiente; puede hacerse antes de R1/R2
R4 (este documento)                ← completo cuando R1-R3 cerrados
```

---

## 7. Decisiones que requieren aprobación de Fidel

| ID | Decisión | Opciones | Estado |
|----|---------|----------|--------|
| D-01 | ¿Crear `u910322706_dfc_restore` en Hostinger? | Panel Hostinger / SSH | Pendiente — Fidel debe crear la BD |
| D-02 | Tamaño mínimo de backup aceptable | 50 KB propuesto | Pendiente — aprobar o ajustar el valor |
| D-03 | MySQL local para R3 vía XAMPP o Docker | XAMPP ya en la máquina | Pendiente — Fidel activa MySQL local |
| D-04 | Retención de backups en producción | 7 backups propuesto | Pendiente — aprobar o ajustar el valor |
| D-05 | `phpunit.mysql.xml` gitignored (credenciales locales) | Gitignored recomendado | **Implementado** — ver backend/.gitignore |
| D-06 | ¿Agregar verificación post-timeout del lado servidor? (si SSH cae después de que mysqldump terminó pero antes del marcador BACKUP_OK, el script aborta aunque el backup fue exitoso) | Verificar `ls ~/backups/` antes de abortar | Pendiente |

---

## 8. Lo que esta SPEC no cubre

- Backup automático programado (cron) en Hostinger — fuera de alcance para plan compartido.
- Replicación de BD o failover.
- Rollback de archivos del frontend (git es suficiente para archivos estáticos).
