# Paleta De Colores — DAG Talento Humano

Extraida del logo de marca (grafito + magenta) y aplicada como tokens CSS en
`frontend/src/app/globals.css`, consumidos por Tailwind via `@theme inline`.
Cubre el dashboard privado y el sitio publico de marketing.

## Colores de marca (fuente)

| Color | Hex | Uso en el logo |
|---|---|---|
| Grafito | `#26262e` | Letra D, anillo de la G, texto "TALENTO HUMANO" |
| Magenta | `#a3175a` | Letra A, linea diagonal, punto, "G" inferior, tagline |

## Tokens — modo claro

| Token | Hex | Uso |
|---|---|---|
| `--background` | `#fbf7f8` | Fondo de pagina |
| `--foreground` / `--navy` | `#26262e` | Texto principal, encabezados |
| `--card` | `#ffffff` | Tarjetas, modales |
| `--card-foreground` | `#26262e` | Texto sobre tarjetas |
| `--primary` | `#a3175a` | Botones primarios, enlaces, foco |
| `--primary-foreground` | `#ffffff` | Texto sobre `--primary` |
| `--muted` | `#f3eaee` | Fondos secundarios, hover sutil |
| `--muted-foreground` | `#7a6b71` | Texto secundario |
| `--border` | `#e7dce0` | Bordes, divisores |
| `--accent` | `#f7e4ec` | Fondos de hover/acento suave |
| `--destructive` | `#c23b2b` | Errores, estados rechazado/vencido |
| `--success` | `#0e8f5c` | Estados aprobado/activo |
| `--warning` | `#b9770e` | Estados pendiente/por vencer |
| `--marketing-shadow` | `0 24px 70px rgb(38 20 30 / 14%)` | Sombra de tarjetas del sitio publico |

## Tokens — modo oscuro (`.dark`)

| Token | Hex | Uso |
|---|---|---|
| `--background` | `#17151a` | Fondo de pagina |
| `--foreground` | `#edeaec` | Texto principal |
| `--card` | `#211f26` | Tarjetas, modales |
| `--card-foreground` | `#edeaec` | Texto sobre tarjetas |
| `--primary` | `#e8579a` | Acento (magenta mas claro, para contraste en fondo oscuro) |
| `--primary-foreground` | `#2b0f1c` | Texto sobre `--primary` |
| `--muted` | `#2a2731` | Fondos secundarios |
| `--muted-foreground` | `#b3a6ac` | Texto secundario |
| `--border` | `#332e36` | Bordes, divisores |
| `--accent` | `#3a1f2c` | Fondos de hover/acento suave |
| `--destructive` | `#f97066` | Errores, estados rechazado/vencido |
| `--success` | `#34d399` | Estados aprobado/activo |
| `--warning` | `#fbbf24` | Estados pendiente/por vencer |

`--navy` no se redefine en oscuro: las paginas publicas que lo usan
(`text-navy`) son solo-claro por diseno y nunca reciben la clase `.dark`.

## Criterio de diseno

- El acento (`--primary`) es siempre el magenta de marca; nunca se usa para
  estados semanticos, para no confundir "seleccionado/enlace" con "error".
- `--destructive` es un rojo-naranja (`#c23b2b` / `#f97066`), deliberadamente
  distinto del magenta de marca, para que un badge de error no se lea como
  un elemento interactivo.
- `--success` y `--warning` se mantienen neutros de marca (verde/ambar
  estandar de UI) en ambos modos.
- El modo oscuro no invierte el magenta: lo aclara (`#a3175a` → `#e8579a`)
  para mantener contraste AA sobre el fondo casi negro.

## Donde vive esto en el codigo

- Fuente de verdad: `frontend/src/app/globals.css` (bloques `:root` y `.dark`).
- Monograma de marca: `frontend/src/components/brand/logo.tsx` (letra "D").
- Favicon: `frontend/src/app/icon.tsx` y `apple-icon.tsx`.
