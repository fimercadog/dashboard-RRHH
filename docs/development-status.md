# Estado De Desarrollo

## Implementado

- Auditoria inicial del repositorio.
- Monorepo con `backend/` Laravel 12 y `frontend/` Next.js 16.
- SQLite inicial con migraciones HRMS principales.
- Seeders demo profesionales: empresa, departamentos, cargos, 20 empleados, usuarios, roles, asistencia, solicitudes, documentos y turnos.
- Dashboard frontend conectado a API real.
- DataTable reusable con TanStack Table, busqueda, paginacion backend, loading, error, vacio y export CSV/PDF.
- Modulos iniciales conectados: empleados, asistencia, vacaciones, permisos, incapacidades, documentos, turnos y auditoria.
- Exportaciones CSV/PDF backend.
- Documentacion inicial.

## Parcial

- Form Requests existen con reglas, pero el CRUD base todavia no inyecta todos los requests tipados por metodo.
- Roles/permisos estan sembrados, pero faltan policies/gates finos por recurso.
- Perfil de empleado tiene estructura visual, pero no carga todos los tabs desde API.
- Reportes muestran catalogo, no todos los reportes tabulares dedicados.
- IA tiene interfaz preparada, sin proveedor conectado.

## Pendiente

- Autenticacion frontend completa.
- CRUD visual completo con formularios shadcn para cada modulo.
- Reclutamiento, novedades, usuarios, roles, configuracion y preferencias completos.
- Tests amplios por workflow critico.
- Push remoto periodico si el remoto esta disponible.
