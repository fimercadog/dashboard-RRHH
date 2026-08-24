# Arquitectura

La plataforma queda separada en dos aplicaciones dentro del repositorio:

- Backend Laravel 12 en `backend/`, API REST y persistencia SQLite inicial.
- Frontend Next.js 16 en `frontend/`, experiencia administrativa privada bajo `/app`.

La API usa modelos Eloquent, migraciones, recursos JSON, services compartidos para consultas tabulares y auditoria, y rutas REST por modulo.

La arquitectura esta preparada para multiempresa mediante `company_id` en las entidades operativas principales. En esta fase demo se resuelve la empresa activa con el usuario autenticado cuando exista o con la primera empresa seed.

## Estado de seguridad

Sanctum y Spatie Permissions estan instalados y se crean roles/permisos demo. La aplicacion aun no tiene flujo completo de login frontend ni policies por recurso, por lo que autorizacion fina queda parcial.
