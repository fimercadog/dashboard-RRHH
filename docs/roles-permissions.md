# Roles Y Permisos

Roles seed:

- Super Admin
- Administrador de empresa
- Recursos Humanos
- Supervisor
- Empleado

Permisos seed:

- `dashboard.view`
- `employees.manage`
- `attendance.manage`
- `requests.approve`
- `documents.manage`
- `reports.view`
- `users.manage`
- `roles.manage`
- `audit.view`
- `settings.manage`

Parcial: los permisos existen en backend, pero faltan policies/gates por modulo y proteccion completa de rutas frontend.

## Usuarios Demo

Todos los usuarios demo usan la contraseña:

`password`

| Usuario | Email | Rol | Puede ver/hacer segun permisos seed |
| --- | --- | --- | --- |
| Sofia Mercado | `superadmin@andespeople.co` | Super Admin | Todo: dashboard, empleados, asistencia, solicitudes, documentos, reportes, usuarios, roles, auditoria y configuracion. |
| Camila Rojas | `admin@andespeople.co` | Administrador de empresa | Todo dentro de la empresa demo: gestion operativa, usuarios, roles, auditoria y configuracion. |
| Sebastian Moreno | `rrhh@andespeople.co` | Recursos Humanos | Dashboard, empleados, asistencia, aprobar solicitudes, documentos y reportes. No usuarios, roles, auditoria ni configuracion. |
| Valentina Castro | `supervisor@andespeople.co` | Supervisor | Dashboard, asistencia, aprobacion de solicitudes y reportes. No gestion completa de empleados/documentos/usuarios. |
| Laura Medina | `empleado@andespeople.co` | Empleado | Dashboard base. Preparado para portal de empleado futuro con acceso a su informacion y solicitudes. |

Nota: estos permisos ya existen en backend con Spatie. La restriccion visual por rol en frontend y las policies/gates finas por modulo siguen pendientes.
