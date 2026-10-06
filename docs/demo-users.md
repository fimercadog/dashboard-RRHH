# Usuarios Demo

Todos los usuarios demo se crean desde `DatabaseSeeder` y usan la contraseña:

`password`

| Email | Password | Rol | Uso recomendado |
| --- | --- | --- | --- |
| `superadmin@andespeople.co` | `password` | Super Admin | Probar acceso global y administracion total. |
| `admin@andespeople.co` | `password` | Administrador de empresa | Probar administracion completa de la empresa demo. |
| `rrhh@andespeople.co` | `password` | Recursos Humanos | Probar operacion diaria de RRHH. |
| `supervisor@andespeople.co` | `password` | Supervisor | Probar aprobaciones y seguimiento de equipo. |
| `empleado@andespeople.co` | `password` | Empleado | Probar vista base del futuro portal de empleado. |

## Permisos Por Rol

| Rol | Permisos |
| --- | --- |
| Super Admin | `dashboard.view`, `employees.manage`, `attendance.manage`, `requests.approve`, `documents.manage`, `reports.view`, `users.manage`, `roles.manage`, `audit.view`, `settings.manage` |
| Administrador de empresa | Igual que Super Admin, limitado conceptualmente a su empresa. |
| Recursos Humanos | `dashboard.view`, `employees.manage`, `attendance.manage`, `requests.approve`, `documents.manage`, `reports.view` |
| Supervisor | `dashboard.view`, `attendance.manage`, `requests.approve`, `reports.view` |
| Empleado | `dashboard.view` |

## Estado Real

Los roles y permisos estan sembrados en backend con Spatie. La autenticacion frontend, el filtrado visual por rol y las policies/gates completas siguen marcadas como parciales.
