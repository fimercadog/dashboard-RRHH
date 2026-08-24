# Estado De Desarrollo

## Implementado

- Auditoria inicial del repositorio.
- Monorepo con `backend/` Laravel 12 y `frontend/` Next.js 16.
- Sitio publico multipagina HRTech SaaS bajo rutas raiz.
- Header publico con dropdowns de Producto y Soluciones.
- Paginas publicas: inicio, producto, modulos, soluciones, reclutamiento, precios, nosotros, blog, articulo, contacto, demo y login visual.
- Componentes marketing reutilizables: layout, header, footer, hero/dashboard preview, feature grid, problem-solution, CTA, AI chat, product page, mockups, contact form.
- SEO tecnico inicial con metadata, sitemap y robots.
- SQLite inicial con migraciones HRMS principales.
- Seeders demo profesionales: empresa, departamentos, cargos, 20 empleados, usuarios, roles, asistencia, solicitudes, documentos y turnos.
- Usuarios demo deterministicos por rol para pruebas: Super Admin, Administrador de empresa, Recursos Humanos, Supervisor y Empleado.
- Login demo funcional con token Sanctum para usuarios sembrados.
- Dashboard frontend conectado a API real.
- DataTable reusable con TanStack Table, busqueda, paginacion backend, loading, error, vacio y export CSV/PDF.
- Modulos iniciales conectados: empleados, asistencia, vacaciones, permisos, incapacidades, documentos, turnos y auditoria.
- Exportaciones CSV/PDF backend.
- Documentacion inicial.

## Parcial

- Formularios publicos son visuales; no envian datos todavia.
- La referencia Job Recruiter se adapto como direccion visual/estructural, no como copia literal ni importacion de assets.
- Form Requests existen con reglas, pero el CRUD base todavia no inyecta todos los requests tipados por metodo.
- Roles/permisos estan sembrados, pero faltan policies/gates finos por recurso.
- La pantalla de login autentica contra backend, pero falta proteccion completa de rutas frontend por rol.
- Perfil de empleado tiene estructura visual, pero no carga todos los tabs desde API.
- Reportes muestran catalogo, no todos los reportes tabulares dedicados.
- IA tiene interfaz preparada, sin proveedor conectado.

## Pendiente

- Autenticacion frontend completa.
- CRUD visual completo con formularios shadcn para cada modulo.
- Reclutamiento, novedades, usuarios, roles, configuracion y preferencias completos.
- Tests amplios por workflow critico.
- Push remoto periodico si el remoto esta disponible.
