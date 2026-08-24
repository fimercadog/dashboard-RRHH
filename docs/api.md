# API

Base URL local: `http://127.0.0.1:8000/api`

Endpoints iniciales:

- `GET /dashboard`
- `GET|POST /employees`
- `GET|PUT|DELETE /employees/{id}`
- `GET|POST /departments`
- `GET|POST /positions`
- `GET|POST /attendances`
- `GET|POST /vacation-requests`
- `GET|POST /permission-requests`
- `GET|POST /sick-leaves`
- `GET|POST /employee-documents`
- `GET|POST /shifts`
- `GET /audit-logs`
- `GET /exports/{resource}.csv`
- `GET /exports/{resource}.pdf`

Parametros tabulares soportados:

- `page`
- `per_page`
- `search`
- `sort`
- `direction`
- `status`
- `employee_id`
- `department_id`
- `position_id`
- `date_from`
- `date_to`

Los endpoints CRUD comparten paginacion backend. Las exportaciones respetan filtros razonables y exportan hasta 5000 filas.
