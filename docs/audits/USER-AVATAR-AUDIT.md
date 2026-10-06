# USER AVATAR AUDIT — UX-007

**Fecha:** 2026-10-06
**Regla:** UX-007 — Los usuarios pueden tener un avatar opcional. Las imágenes se seleccionan mediante upload, nunca mediante rutas técnicas. El sistema debe proporcionar un fallback visual cuando no exista imagen.
**Commit:** bd7fc5b

---

## DEFINITION OF DONE

| Item | Estado |
|------|--------|
| BD alineada (`users.avatar_path nullable`) | ✅ |
| Backend upload `POST /users/{id}/avatar` | ✅ |
| Backend delete `DELETE /users/{id}/avatar` | ✅ |
| Storage `public` disk, company scope | ✅ |
| Validación MIME jpg/jpeg/png/webp, max 2MB | ✅ |
| Reemplazo automático al re-subir | ✅ |
| Eliminación del archivo viejo al reemplazar/borrar | ✅ |
| `UserResource.avatar_url` | ✅ |
| `AuthController::userPayload()` avatar_url | ✅ |
| `AuthUser` TS type: avatar_url | ✅ |
| `AppUser` TS type: avatar_url | ✅ |
| Frontend tabla usuarios: avatar/iniciales + nombre | ✅ |
| Frontend crear usuario: campo file | ✅ |
| Frontend editar avatar: AvatarActionDialog (preview, upload, delete) | ✅ |
| Fallback iniciales (2 letras) | ✅ |
| Sidebar header: avatar o iniciales del usuario autenticado | ✅ |
| Actualiza localStorage del usuario activo tras cambio | ✅ |
| Build sin errores | ✅ |
| Git commit bd7fc5b | ✅ |
| Push origin/master | ✅ |
| Backend deploy (git pull, migrate, caches, storage:link) | ✅ |
| Frontend deploy (static export a public_html) | ✅ |

---

## CAMBIOS IMPLEMENTADOS

### Backend

| Archivo | Cambio |
|---------|--------|
| `migrations/2026_10_06_153000_add_avatar_to_users_table.php` | Nuevo — `users.avatar_path nullable` |
| `Models/User.php` | `avatar_path` en `$fillable` |
| `Resources/UserResource.php` | `avatar_url` via `Storage::disk('public')->url()` |
| `Controllers/Api/UserController.php` | `uploadAvatar()`, `deleteAvatar()`, avatar en `store()` |
| `Controllers/Api/AuthController.php` | `avatar_url` en `userPayload()` |
| `routes/api.php` | `POST /users/{user}/avatar`, `DELETE /users/{user}/avatar` |

### Frontend

| Archivo | Cambio |
|---------|--------|
| `src/lib/auth.ts` | `AuthUser.avatar_url?: string | null` |
| `src/lib/types.ts` | `AppUser.avatar_url?: string | null` |
| `src/components/layout/admin-shell.tsx` | Sidebar: img avatar o iniciales fallback |
| `src/app/app/usuarios/page.tsx` | Columna avatar+nombre, file field en create, AvatarActionDialog |

---

## EXCEPCIONES

| Item | Decisión |
|------|----------|
| Preview inline en CrudModal edit | Implementado como diálogo dedicado — más limpio |
| storage:link producción | Ejecutado en deploy |
| Tests automatizados | Pendiente prueba manual en producción |

---

## REGLA PARA NUEVAS VERTICALES

Heredan `avatar_url` automáticamente via `UserResource` y `AuthUser`.
Para otros modelos con imagen: campo `_path nullable`, `->store("avatars/{cid}", 'public')`, `Storage::url()` en Resource, componente con fallback iniciales.
