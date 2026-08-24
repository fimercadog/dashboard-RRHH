# Base De Datos

SQLite es la persistencia inicial. Las migraciones evitan logica especifica de SQLite para facilitar migracion posterior a MySQL.

Tablas HRMS implementadas:

- `companies`
- `departments`
- `positions`
- `employees`
- `attendances`
- `vacation_requests`
- `permission_requests`
- `sick_leaves`
- `employee_documents`
- `shifts`
- `shift_assignments`
- `audit_logs`
- `users`
- tablas de Sanctum
- tablas de Spatie Permissions

`Employee` y `User` son entidades separadas. `users.employee_id` es nullable.
