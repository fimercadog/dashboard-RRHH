# Historias de Usuario — DFC Talento Humano ERP

> **Proyecto:** dfctalentohumano  
> **Fecha:** 2026-10-13  
> **Versión:** 1.0  
> Cada HU sigue el formato estándar: _Como [rol], quiero [acción], para [beneficio]._  
> Los roles usados son: **Administrador**, **Empleado**, **Reclutador**, **Contador**, **Vendedor**, **Comprador**, **Super Admin**.

---

## MÓDULO 1 — AUTENTICACIÓN Y ACCESO

**HU-001** — Inicio de sesión  
Como usuario registrado, quiero ingresar con mi correo y contraseña, para acceder al sistema de forma segura sin que otros vean mis datos.

**HU-002** — Cierre de sesión  
Como usuario autenticado, quiero cerrar sesión desde cualquier pantalla, para proteger mi cuenta cuando termino de trabajar.

**HU-003** — Recuperación de contraseña  
Como usuario que olvidó su contraseña, quiero solicitar un enlace de restablecimiento por correo, para recuperar el acceso sin contactar soporte.

---

## MÓDULO 2 — DASHBOARD Y NAVEGACIÓN

**HU-004** — Dashboard general  
Como administrador, quiero ver un resumen visual de métricas clave (empleados activos, ventas del mes, leads, alertas de inventario), para tomar decisiones rápidas sin abrir cada módulo.

**HU-005** — Navegación por el sistema  
Como cualquier usuario, quiero un menú lateral que liste los módulos a los que tengo acceso, para desplazarme rápidamente sin depender del botón "atrás".

**HU-006** — Modo contingencia  
Como usuario, quiero que el sistema me permita seguir operando cuando hay falla de conectividad (modo offline), para que las interrupciones de red no detengan el trabajo.

---

## MÓDULO 3 — ORGANIZACIÓN Y EMPRESA

**HU-007** — Datos de la empresa  
Como administrador, quiero configurar el nombre, logo, NIT, dirección y datos fiscales de mi empresa, para que aparezcan correctamente en documentos y reportes generados.

**HU-008** — Gestión de departamentos  
Como administrador, quiero crear, editar y eliminar departamentos de la empresa, para organizar a los empleados por área funcional.

**HU-009** — Gestión de cargos / posiciones  
Como administrador, quiero definir los cargos disponibles y asignarlos a departamentos, para estructurar el organigrama correctamente.

**HU-010** — Gestión de turnos  
Como administrador, quiero crear turnos de trabajo (nombre, hora entrada, hora salida), para asignarlos a los empleados y controlar horarios.

---

## MÓDULO 4 — USUARIOS Y ROLES

**HU-011** — Gestión de usuarios  
Como administrador, quiero crear usuarios del sistema, asignarles rol y empresa, y activarlos o inactivarlos, para controlar quién tiene acceso.

**HU-012** — Gestión de roles y permisos  
Como administrador, quiero crear roles con permisos específicos por módulo (ver, crear, editar, eliminar), para que cada usuario solo acceda a lo que le corresponde.

**HU-013** — Asignación de rol a usuario  
Como administrador, quiero cambiar el rol de un usuario sin eliminar su historial, para ajustar responsabilidades sin perder trazabilidad.

---

## MÓDULO 5 — EMPLEADOS

**HU-014** — Registro de empleado  
Como administrador de RRHH, quiero registrar un empleado con nombre, cédula, cargo, departamento, turno, salario y fecha de ingreso, para tener su expediente completo desde el primer día.

**HU-015** — Perfil detallado del empleado  
Como administrador de RRHH, quiero ver el perfil completo de un empleado (datos personales, contrato, historial de salario, documentos), para tener toda la información en un solo lugar.

**HU-016** — Edición de datos del empleado  
Como administrador de RRHH, quiero editar los datos de un empleado existente, para mantener el expediente actualizado ante cambios de cargo, salario o datos personales.

**HU-017** — Inactivación de empleado  
Como administrador de RRHH, quiero marcar un empleado como inactivo sin eliminarlo, para conservar el historial y cumplir requisitos legales.

**HU-018** — Gestión de documentos del empleado  
Como administrador de RRHH, quiero cargar y consultar documentos asociados a cada empleado (contrato, cédula, certificados), para tener un repositorio digital organizado.

**HU-019** — Importación masiva de empleados  
Como administrador de RRHH, quiero importar empleados desde un archivo Excel/CSV, para agilizar la carga inicial o migraciones de datos.

---

## MÓDULO 6 — ASISTENCIA

**HU-020** — Registro de asistencia  
Como administrador de RRHH, quiero registrar entradas y salidas de empleados por fecha, para controlar la puntualidad y las horas trabajadas.

**HU-021** — Consulta de historial de asistencia  
Como administrador de RRHH, quiero filtrar el historial de asistencia por empleado y rango de fechas, para generar informes de ausentismo o puntualidad.

---

## MÓDULO 7 — VACACIONES Y PERMISOS

**HU-022** — Solicitud de vacaciones  
Como empleado, quiero solicitar vacaciones indicando las fechas, para que RRHH las apruebe o rechace formalmente.

**HU-023** — Aprobación de vacaciones  
Como administrador de RRHH, quiero aprobar o rechazar solicitudes de vacaciones con un comentario, para gestionar la disponibilidad del equipo.

**HU-024** — Solicitud de permiso  
Como empleado, quiero solicitar un permiso (día libre, salida temprana) con motivo y fecha, para que quede registrado formalmente.

**HU-025** — Aprobación de permisos  
Como administrador de RRHH, quiero aprobar o rechazar solicitudes de permiso, para mantener control sobre la asistencia del personal.

---

## MÓDULO 8 — INCAPACIDADES

**HU-026** — Registro de incapacidad  
Como administrador de RRHH, quiero registrar una incapacidad médica para un empleado con fecha de inicio, fin y diagnóstico, para tener trazabilidad de ausencias por salud.

**HU-027** — Consulta de incapacidades  
Como administrador de RRHH, quiero consultar el historial de incapacidades por empleado, para analizar patrones de salud y ausentismo.

---

## MÓDULO 9 — RECLUTAMIENTO

**HU-028** — Creación de vacante  
Como reclutador, quiero publicar una vacante con cargo, descripción, requisitos y estado (abierta/cerrada), para iniciar el proceso de selección formalmente.

**HU-029** — Registro de candidato  
Como reclutador, quiero registrar candidatos con nombre, correo, CV y cargo al que aplica, para gestionar el pipeline de selección.

**HU-030** — Gestión de postulaciones  
Como reclutador, quiero vincular candidatos a vacantes y ver el estado de cada postulación (en revisión, preseleccionado, rechazado), para tener visibilidad del proceso.

**HU-031** — Programación de entrevistas  
Como reclutador, quiero agendar entrevistas para candidatos seleccionados con fecha, hora y entrevistador, para organizar el proceso de evaluación.

**HU-032** — Resultado de entrevista  
Como reclutador, quiero registrar el resultado de una entrevista (aprobado/rechazado/pendiente) con observaciones, para avanzar o cerrar la postulación con evidencia.

---

## MÓDULO 10 — INVENTARIO

**HU-033** — Gestión de categorías  
Como administrador de inventario, quiero crear y organizar categorías de productos, para clasificar el catálogo de forma estructurada.

**HU-034** — Gestión de marcas  
Como administrador de inventario, quiero registrar las marcas de los productos, para filtrar y reportar por fabricante.

**HU-035** — Gestión de unidades de medida  
Como administrador de inventario, quiero definir unidades de medida (kg, litro, unidad, caja), para que los productos tengan una unidad consistente en toda la cadena.

**HU-036** — Gestión de bodegas  
Como administrador de inventario, quiero crear bodegas o almacenes con nombre y ubicación, para gestionar stock por localización física.

**HU-037** — Registro de producto  
Como administrador de inventario, quiero registrar un producto con nombre, SKU, categoría, marca, unidad, precio de costo y precio de venta, para tenerlo disponible en ventas y compras.

**HU-038** — Consulta de stock  
Como administrador de inventario, quiero ver el stock actual de cada producto por bodega, para saber cuánto hay disponible en cada ubicación.

**HU-039** — Movimientos de inventario  
Como administrador de inventario, quiero ver todos los movimientos de entrada y salida de inventario con su origen (compra, venta, ajuste), para auditar cambios de stock.

**HU-040** — Ajuste de inventario  
Como administrador de inventario, quiero registrar un ajuste manual de stock con motivo, para corregir diferencias detectadas en conteos físicos.

**HU-041** — Importación masiva de productos  
Como administrador de inventario, quiero importar productos desde Excel/CSV, para cargar catálogos grandes sin ingresarlos uno a uno.

---

## MÓDULO 11 — COMPRAS

**HU-042** — Registro de proveedor  
Como comprador, quiero registrar proveedores con nombre, NIT, contacto y condiciones de pago, para gestionarlos en órdenes y facturas.

**HU-043** — Creación de orden de compra  
Como comprador, quiero crear una orden de compra con proveedor, productos, cantidades y precios, para formalizar la solicitud de mercancía.

**HU-044** — Recepción de mercancía  
Como comprador, quiero registrar la recepción de productos de una orden de compra, para actualizar el inventario y confirmar la entrega del proveedor.

**HU-045** — Factura de compra  
Como comprador, quiero registrar la factura emitida por el proveedor asociada a una orden, para llevar la contabilidad de las compras.

**HU-046** — Cuentas por pagar (CxP)  
Como contador, quiero ver el listado de facturas de compra pendientes de pago con sus vencimientos, para priorizar pagos y evitar moras.

---

## MÓDULO 12 — VENTAS

**HU-047** — Creación de cotización  
Como vendedor, quiero generar una cotización para un cliente con productos, cantidades y precios, para presentarle una oferta formal antes de confirmar la venta.

**HU-048** — Conversión de cotización a pedido  
Como vendedor, quiero convertir una cotización aprobada en un pedido de venta, para iniciar el proceso de despacho sin volver a ingresar los datos.

**HU-049** — Pedido de venta  
Como vendedor, quiero crear y gestionar pedidos de venta con estado (pendiente, en proceso, despachado), para dar seguimiento a las ventas confirmadas.

**HU-050** — Factura de venta  
Como vendedor, quiero generar una factura de venta a partir de un pedido, para emitir el documento de cobro al cliente.

**HU-051** — Cuentas por cobrar (CxC)  
Como contador, quiero ver las facturas de venta pendientes de cobro con fechas de vencimiento, para gestionar la cartera y hacer seguimiento de pagos.

---

## MÓDULO 13 — CLIENTES Y LEADS

**HU-052** — Registro de cliente  
Como vendedor, quiero registrar un cliente con nombre, NIT, dirección, contacto y condiciones comerciales, para gestionarlo en cotizaciones, pedidos y facturas.

**HU-053** — Historial del cliente  
Como vendedor, quiero ver el historial de compras, pagos y comunicaciones de un cliente, para dar un servicio personalizado y anticipar necesidades.

**HU-054** — Notas sobre el cliente  
Como vendedor, quiero registrar notas internas sobre un cliente (conversaciones, acuerdos), para que cualquier miembro del equipo tenga contexto completo.

**HU-055** — Registro de lead  
Como vendedor, quiero registrar un prospecto o lead con datos de contacto y fuente de origen, para iniciar el seguimiento comercial antes de convertirlo en cliente.

**HU-056** — Seguimiento de lead  
Como vendedor, quiero actualizar el estado de un lead (nuevo, contactado, calificado, perdido), para gestionar el embudo de ventas.

---

## MÓDULO 14 — CRM

**HU-057** — Gestión de contactos  
Como vendedor, quiero registrar contactos asociados a clientes o empresas con nombre, cargo, correo y teléfono, para tener un directorio de interlocutores comerciales.

**HU-058** — Gestión de deals (oportunidades)  
Como vendedor, quiero crear oportunidades de negocio con valor estimado, etapa y fecha de cierre esperada, para gestionar el pipeline de ventas.

**HU-059** — Registro de actividades CRM  
Como vendedor, quiero registrar actividades (llamada, reunión, email) vinculadas a un deal o contacto, para tener trazabilidad de todas las interacciones comerciales.

**HU-060** — Segmentación de clientes  
Como gerente comercial, quiero segmentar clientes por criterios (industria, tamaño, región, valor), para diseñar campañas dirigidas y análisis de cartera.

**HU-061** — Campañas de marketing  
Como gerente comercial, quiero crear campañas y asignarles segmentos de clientes, para medir el impacto de cada acción comercial.

---

## MÓDULO 15 — WHATSAPP

**HU-062** — Configuración de WhatsApp  
Como administrador, quiero conectar el sistema a la API de WhatsApp Business con token y número verificado, para habilitar la mensajería desde el ERP.

**HU-063** — Bandeja de conversaciones de WhatsApp  
Como agente de atención, quiero ver todas las conversaciones de WhatsApp en una bandeja unificada con estado (activa, resuelta), para gestionar las consultas de clientes sin salir del sistema.

**HU-064** — Responder conversación de WhatsApp  
Como agente de atención, quiero leer y responder mensajes de WhatsApp desde el sistema, para atender clientes sin abrir la app de WhatsApp.

**HU-065** — Asignación de conversación a agente  
Como supervisor, quiero asignar una conversación de WhatsApp a un agente específico, para distribuir la carga de atención del equipo.

---

## MÓDULO 16 — INTELIGENCIA ARTIFICIAL

**HU-066** — Configuración de IA  
Como administrador, quiero configurar la clave API y el modelo de IA a usar (GPT, Claude, etc.), para habilitar funciones de asistencia inteligente en el sistema.

**HU-067** — Asistente IA en módulos  
Como usuario, quiero poder consultar al asistente de IA dentro del sistema para analizar datos, redactar textos o resolver dudas contextuales, para acelerar tareas que normalmente tomarían más tiempo.

---

## MÓDULO 17 — FINANZAS

**HU-068** — Gestión de cuentas de caja/banco  
Como contador, quiero registrar cuentas de caja y banco con saldo inicial y moneda, para gestionar el flujo de efectivo de la empresa.

**HU-069** — Registro de pagos  
Como contador, quiero registrar pagos a proveedores o recibos de clientes vinculados a facturas, para mantener las CxP y CxC actualizadas.

**HU-070** — Transferencias entre cuentas  
Como contador, quiero registrar transferencias entre cuentas internas (de caja a banco o entre bancos), para reflejar los movimientos de tesorería.

**HU-071** — Movimientos financieros  
Como contador, quiero ver el listado de todos los movimientos financieros (ingresos, egresos, transferencias) con filtros por fecha y cuenta, para controlar el flujo de caja.

---

## MÓDULO 18 — CONTABILIDAD

**HU-072** — Plan de cuentas  
Como contador, quiero configurar el plan de cuentas contables de la empresa (código, nombre, tipo), para registrar todas las transacciones con la estructura correcta.

**HU-073** — Configuración de cuentas contables para módulos  
Como contador, quiero asignar cuentas contables a conceptos del sistema (ventas, compras, caja, impuestos), para que los asientos se generen automáticamente.

**HU-074** — Libro diario  
Como contador, quiero ver y registrar asientos contables en el libro diario con fecha, cuentas, débitos y créditos, para llevar la contabilidad completa.

**HU-075** — Gestión de periodos contables  
Como contador, quiero abrir y cerrar periodos contables por mes/año, para controlar qué fechas se pueden modificar y proteger la información ya revisada.

---

## MÓDULO 19 — IMPORTACIÓN MASIVA

**HU-076** — Importar datos desde archivo  
Como administrador, quiero subir un archivo Excel o CSV para importar en lote empleados, productos, clientes o proveedores, para no ingresar registros uno a uno en migraciones o cargas masivas.

**HU-077** — Vista previa y validación de importación  
Como administrador, quiero ver una previa de los datos a importar con errores resaltados antes de confirmar, para corregir el archivo antes de que entren datos incorrectos al sistema.

**HU-078** — Plantilla de importación descargable  
Como administrador, quiero descargar una plantilla del formato correcto para cada módulo, para saber exactamente qué columnas y formatos acepta el sistema.

---

## MÓDULO 20 — REPORTES

**HU-079** — Reportes por módulo  
Como gerente, quiero generar reportes de los principales módulos (ventas, compras, nómina, inventario) con filtros por fecha y parámetros clave, para tomar decisiones basadas en datos.

**HU-080** — Exportar reporte a Excel/PDF  
Como gerente, quiero exportar cualquier reporte a Excel o PDF, para compartirlo con terceros o archivarlo fuera del sistema.

---

## MÓDULO 21 — AUDITORÍA

**HU-081** — Log de auditoría  
Como administrador o auditor, quiero ver un registro de todos los cambios realizados en el sistema (quién hizo qué y cuándo), para detectar errores o acciones no autorizadas.

**HU-082** — Filtrar log de auditoría  
Como administrador, quiero filtrar el log de auditoría por usuario, módulo, acción y rango de fechas, para encontrar rápidamente el evento que investigo.

---

## MÓDULO 22 — CONFIGURACIÓN DEL SISTEMA

**HU-083** — Configuración general  
Como administrador, quiero ajustar parámetros generales del sistema (nombre de empresa, zona horaria, moneda, idioma), para personalizar el ERP a las necesidades de la organización.

**HU-084** — Configuración de integraciones externas  
Como administrador, quiero ver el estado de las integraciones activas (WhatsApp, IA, etc.) y activarlas o desactivarlas, para controlar qué servicios externos usa el sistema.

---

## MÓDULO 23 — DOCUMENTOS

**HU-085** — Repositorio de documentos  
Como usuario autorizado, quiero subir, organizar y descargar documentos (contratos, políticas, actas) dentro del sistema, para centralizar el archivo documental de la empresa.

---

## RESUMEN DE HISTORIAS

| Rango | Módulo | Cantidad |
|-------|--------|----------|
| HU-001 – HU-003 | Autenticación | 3 |
| HU-004 – HU-006 | Dashboard y navegación | 3 |
| HU-007 – HU-010 | Organización | 4 |
| HU-011 – HU-013 | Usuarios y roles | 3 |
| HU-014 – HU-019 | Empleados | 6 |
| HU-020 – HU-021 | Asistencia | 2 |
| HU-022 – HU-025 | Vacaciones y permisos | 4 |
| HU-026 – HU-027 | Incapacidades | 2 |
| HU-028 – HU-032 | Reclutamiento | 5 |
| HU-033 – HU-041 | Inventario | 9 |
| HU-042 – HU-046 | Compras | 5 |
| HU-047 – HU-051 | Ventas | 5 |
| HU-052 – HU-056 | Clientes y leads | 5 |
| HU-057 – HU-061 | CRM | 5 |
| HU-062 – HU-065 | WhatsApp | 4 |
| HU-066 – HU-067 | IA | 2 |
| HU-068 – HU-071 | Finanzas | 4 |
| HU-072 – HU-075 | Contabilidad | 4 |
| HU-076 – HU-078 | Importación masiva | 3 |
| HU-079 – HU-080 | Reportes | 2 |
| HU-081 – HU-082 | Auditoría | 2 |
| HU-083 – HU-084 | Configuración | 2 |
| HU-085 | Documentos | 1 |
| **TOTAL** | | **85** |
