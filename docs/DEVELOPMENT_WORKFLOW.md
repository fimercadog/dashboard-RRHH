# DEVELOPMENT_WORKFLOW.md — Procedimiento obligatorio

## Antes de programar cualquier cosa

1. Leer `docs/PROJECT_STATE.md` — qué existe y en qué estado.
2. Leer `docs/CORE_CONTRACT.md` — qué no se toca y por qué.
3. Si es una vertical nueva o extensión, leer `docs/VERTICALS.md`.
4. Si la tarea toca Compras/Ventas/Finanzas/Contabilidad, leer `docs/arquitectura-fases-c-f.md`.
5. Inspeccionar el código real relevante antes de escribir (no asumir desde memoria).
6. Buscar si ya existe: componente, endpoint, servicio, hook, validación — BUSCAR ANTES DE CREAR.
7. Crear plan: qué capas toca, qué cambia, qué reutiliza.
8. Definir criterios de aceptación: ¿cómo sé que está terminado?

---

## Checklist de capas por tarea

Para cualquier tarea que añade o modifica una funcionalidad, verificar CADA capa relevante.
Una tarea NO está DONE hasta que todas las capas aplicables están implementadas y verificadas.

### Backend
- [ ] Migración de BD (si hay tabla nueva o columna nueva)
- [ ] Modelo Eloquent con fillable, casts, relaciones
- [ ] Controller extiende BaseCrudController o tiene lógica justificada
- [ ] Resource JSON para la respuesta API
- [ ] FormRequest con validación (StoreXxxRequest)
- [ ] Rutas en `routes/api.php` dentro del grupo Sanctum
- [ ] Permisos registrados (si hay nueva vertical)

### Base de datos
- [ ] Migración tiene `company_id`
- [ ] Claves foráneas correctas
- [ ] No modifica tablas del core sin justificación

### Servicios
- [ ] Si toca stock → pasa por StockService (nunca directo)
- [ ] Auditoría registrada (BaseCrudController lo hace automático; controllers manuales deben llamar AuditService)

### Frontend
- [ ] Página creada en `src/app/app/{modulo}/page.tsx`
- [ ] Usa `ModuleTablePage` o un layout justificado
- [ ] Columnas definidas con tipos correctos
- [ ] Campos de CrudModal definidos (CrudField[])
- [ ] Link en el nav/sidebar del admin shell
- [ ] Manejo de loading, error, estado vacío

### Permisos y autorización
- [ ] Permiso verificado en el backend (middleware o gate)
- [ ] Permiso verificado en el frontend (filtro de nav o guard de página)

### Responsive / móvil
- [ ] Tabla funciona en pantallas pequeñas (scroll horizontal o cards)
- [ ] Modal funciona en móvil
- [ ] No hay overflow horizontal

### Verificación final
- [ ] `php artisan migrate:fresh --seed` pasa sin errores
- [ ] `npm run build` pasa sin errores TypeScript
- [ ] La funcionalidad se puede usar end-to-end en el navegador
- [ ] No se rompió ningún módulo existente

---

## Regla de completitud por capas

**NUNCA declarar DONE si alguna de estas situaciones aplica:**
- ✗ El backend existe pero no hay rutas en api.php
- ✗ Las rutas existen pero no hay página frontend
- ✗ La página frontend existe pero no hay link en el nav
- ✗ El CRUD existe pero falta la migración de permisos
- ✗ El código compila pero `migrate:fresh --seed` falla
- ✗ La funcionalidad existe en desktop pero rompe en móvil

---

## Orden de implementación de una vertical nueva

1. **Migración de permisos** → `add_{vertical}_permissions.php`
2. **Migraciones de tablas** → con company_id y FKs correctas
3. **Modelos Eloquent** → fillable, casts, relaciones
4. **Service** (si la vertical necesita lógica compleja o integra con StockService)
5. **Resources JSON** → representación de cada entidad
6. **FormRequests** → validaciones de store/update
7. **Controllers** → extendiendo BaseCrudController
8. **Rutas** → en api.php
9. **Frontend** → página con ModuleTablePage
10. **Nav** → link en admin-shell
11. **Verificación** → checklist completo arriba

---

## Aprendizajes que no deben olvidarse

Ver `docs/DEVELOPMENT_MEMORY.md` para el historial completo. Resumen rápido:

| Error frecuente | Regla |
|-----------------|-------|
| "Faltó frontend" | Backend + BD + API + Frontend = una sola tarea. No dividir artificialmente. |
| "Faltó la migración" | Migración PRIMERO, siempre. La migración de permisos también. |
| "Faltaron permisos" | Toda vertical nueva tiene su migración de permisos. Verificar en el checklist. |
| "Rompiste algo existente" | `migrate:fresh --seed` + recorrer el módulo existente antes de declarar DONE. |
| "Esto ya existía" | BUSCAR → REUTILIZAR → EXTENDER → CREAR. Nunca al revés. |
| "stock fuera de StockService" | El único escritor de stock es StockService. Siempre. Sin excepción. |
| "APP_ENV=production sin --force" | `php artisan migrate --force` en producción. Ver `docs/artisan-force-flag.md`. |
