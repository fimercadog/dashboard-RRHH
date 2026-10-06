# SALES_ENVIRONMENT_AUDIT.md — Auditoría del entorno comercial
> Fecha: 2026-10-04. Estado: diagnóstico previo a construcción. NO instalar nada todavía.

---

## 1. Claude Code — configuración actual

| Parámetro | Valor |
|-----------|-------|
| Modelo | claude-sonnet-4-6 |
| Effort level global | high |
| Effort level sonnet | medium |
| Auto-updates | off (manual) |
| Experimental teams | habilitado |
| Hooks activos | SessionStart (reminder), Stop/SessionEnd (codemem ingest) |

---

## 2. MCPs instalados y estado

### Locales (settings.json)
| MCP | Estado | Función |
|-----|--------|---------|
| `codemem` | ✅ Activo | Memoria persistente entre sesiones |
| `context-mode` | ✅ Activo | Compresión de outputs grandes |

### Conectados vía claude.ai (cloud)
| MCP | Estado | Relevancia comercial |
|-----|--------|---------------------|
| **Gmail** | ✅ Autenticado | ★★★★★ Outreach + seguimiento |
| **Google Calendar** | ✅ Autenticado | ★★★★ Agendar demos |
| **Google Drive** | ✅ Autenticado | ★★★ CRM en Sheets, documentos |
| **Miro** | ✅ Autenticado | ★★ Visualización de pipelines |
| **Windsor.ai** | ✅ Autenticado | ★★★ 350+ conectores (Meta Ads, Google Ads, HubSpot, Klaviyo, etc.) |
| **Claude Docs** | ✅ Autenticado | ★★ Documentación compartida |
| chrome-devtools | ✅ Activo | ★ Testing web |
| claude-in-chrome | ✅ Activo | ★★ Automatización browser |
| context7 | ✅ Activo | Docs de librerías |
| obsidian-vault | ✅ Activo | Wiki del proyecto |

### No instalados / No autenticados (requieren acción de Fidel)
| MCP | Estado | Notas |
|-----|--------|-------|
| HubSpot | 🔒 Requiere auth | Windsor.ai ya tiene conector HubSpot |
| LeadMagic | ❌ No instalado | Ver evaluación abajo |
| n8n | ❌ No instalado | Ver evaluación abajo |

---

## 3. Skills disponibles con relevancia comercial ALTA

### Prospección y enriquecimiento
| Skill | Estado | Función |
|-------|--------|---------|
| `nimble:local-places` | ✅ Disponible | Encontrar clínicas veterinarias locales por ciudad/tipo |
| `nimble:company-deep-dive` | ✅ Disponible | Investigación profunda de empresa |
| `nimble:healthcare-providers-extract` | ✅ Disponible | Extraer proveedores de salud (aplica a veterinarias) |
| `nimble:healthcare-providers-enrich` | ✅ Disponible | Enriquecer datos de proveedores |
| `nimble:healthcare-providers-verify` | ✅ Disponible | Verificar datos |
| `nimble:talent-sourcing` | ✅ Disponible | Encontrar decisores por empresa |
| `nimble:market-finder` | ✅ Disponible | Segmentar mercados |
| `nimble:meeting-prep` | ✅ Disponible | Preparar reuniones con contexto del prospecto |
| `apollo:prospect` | ✅ Disponible | Prospección con Apollo.io |
| `apollo:enrich-lead` | ✅ Disponible | Enriquecimiento de leads |
| `apollo:sequence-load` | ✅ Disponible | Cargar secuencias en Apollo |
| `vpai:vibe-prospecting` | ✅ Disponible | Prospección basada en señales |
| `nimble:competitor-intel` | ✅ Disponible | Inteligencia competitiva |
| `nimble:competitor-positioning` | ✅ Disponible | Posicionamiento vs competencia |

### Outreach y ventas
| Skill | Estado | Función |
|-------|--------|---------|
| `sales:account-research` | ✅ | Investigar cuenta antes del contacto |
| `sales:draft-outreach` | ✅ | Redactar mensaje inicial |
| `sales:call-prep` | ✅ | Preparar llamada/demo |
| `sales:stakeholder-map` | ✅ | Mapear decisores en la empresa |
| `sales:deal-review` | ✅ | Revisar oportunidad |
| `sales:handle-objection` | ✅ | Manejar objeciones |
| `marketing-skills:cold-email` | ✅ | Templates de cold email |
| `marketing-skills:copywriting` | ✅ | Copy de contacto |
| `marketing-skills:emails` | ✅ | Email sequences |
| `marketing-skills:competitor-profiling` | ✅ | Perfil de competidor |

### Investigación y scraping
| Skill | Estado | Función |
|-------|--------|---------|
| `firecrawl:firecrawl-search` | ✅ | Búsqueda web con Firecrawl |
| `firecrawl:firecrawl-scrape` | ✅ | Scraping de páginas web |
| `brightdata-plugin:live-research` | ✅ | Research en tiempo real |
| `brightdata-plugin:competitive-intel` | ✅ | Intel competitiva |
| `brightdata-plugin:search` | ✅ | Búsqueda web avanzada |
| WebSearch | ✅ Built-in | Búsqueda general |
| WebFetch | ✅ Built-in | Obtener contenido de URLs |

---

## 4. Evaluación de herramientas propuestas

### LeadMagic MCP
**Problema que resuelve:** Encontrar emails profesionales verificados de decisores.
**¿Ya existe algo equivalente?** SÍ — Apollo.io (`apollo:enrich-lead`) cubre enriquecimiento con emails. Nimble cubre discovery.
**Recomendación:** POSTPONER. Probar primero Apollo + Nimble. Si hay gaps específicos de cobertura (ej. emails latinoamericanos), evaluar entonces.
**Costo:** Créditos por consulta. Desconocido sin cuenta.

### n8n
**Problema que resuelve:** Automatización de workflows (disparar búsquedas, actualizar CRM, enviar emails programados).
**¿Ya existe algo equivalente?** Claude puede orquestar workflows directamente. Gmail MCP puede enviar. El loop de Claude puede ejecutar pasos.
**Recomendación:** POSTPONER. Para la primera prueba de 10 veterinarias, no se necesita. Si el sistema escala a 100+ prospectos por semana con pipelines complejos, evaluar entonces.
**Costo:** Self-hosted (sin costo) o cloud.

### HubSpot
**Problema que resuelve:** CRM comercial completo.
**¿Ya existe algo equivalente?** Windsor.ai ya tiene conector HubSpot. También se puede usar Google Sheets como CRM mínimo.
**Recomendación:** Usar Google Sheets inicialmente (gratis, ya conectado). Si se necesita HubSpot, autenticar el conector en claude.ai settings.

### Google Maps / Places
**Problema que resuelve:** Encontrar clínicas veterinarias por geografía.
**¿Ya existe algo equivalente?** SÍ — `nimble:local-places` hace exactamente esto. `brightdata-plugin:live-research` también.
**Recomendación:** Usar Nimble Local Places. No instalar Maps API aparte.

---

## 5. Stack mínimo viable — YA DISPONIBLE sin instalar nada

```
DISCOVER        → nimble:local-places + nimble:healthcare-providers-extract
RESEARCH        → nimble:company-deep-dive + firecrawl:scrape + WebSearch
ENRICH          → apollo:enrich-lead + nimble:healthcare-providers-enrich
IDENTIFY DM     → nimble:talent-sourcing + nimble:meeting-prep
VALIDATE        → nimble:healthcare-providers-verify
SCORE           → Claude (lógica de scoring con ICP definido)
PERSONALIZE     → sales:account-research + marketing-skills:cold-email
CRM             → Google Sheets vía Google Drive MCP (sin instalar nada)
OUTREACH        → Gmail MCP (correos) + Claude (mensajes WhatsApp/LinkedIn para Fidel)
FOLLOW-UP       → Gmail MCP + Google Calendar MCP
ESCALATE        → Claude prepara el hot-lead summary para Fidel
MEMORY          → codemem + Obsidian vault
```

**0 herramientas nuevas que instalar para arrancar.**

---

## 6. Herramientas que NO debemos instalar ahora

| Herramienta | Razón |
|-------------|-------|
| LeadMagic | Apollo + Nimble cubren el caso de uso. Evaluar si hay gap después. |
| n8n | Sobreeingeniería para 10 prospectos. Agregar cuando escale. |
| Playwright dedicado | chrome-devtools + claude-in-chrome ya disponibles. |
| HubSpot MCP separado | Windsor.ai ya lo cubre. Google Sheets suficiente para MVP. |
| 40 skills más | YAGNI. El stack arriba ya es completo. |

---

## 7. Riesgos identificados

| Riesgo | Mitigación |
|--------|-----------|
| Apollo.io requiere cuenta/API key | Verificar si está autenticado antes de usarlo |
| Nimble MCP requiere autenticación | Verificar en claude.ai connector settings |
| Gmail outreach puede marcar spam | Máximo 20-30 emails/día, personalizados, sin attachments |
| Datos de veterinarias incompletos | Marcar como "desconocido" si no hay fuente; no inventar |
| Duplicados de prospectos | CRM en Sheets con columna ID único (website URL como PK) |
| Créditos consumidos sin valor | Filtrar primero con búsqueda gratuita; enriquecer solo A/B |

---

## 8. Próximos pasos (en orden, esperando aprobación de Fidel)

1. **[FIDEL debe hacer]** Verificar que Nimble y Apollo estén autenticados en claude.ai connectors.
2. **Definir ICP** — `ICP.md` con criterios de veterinaria ideal.
3. **Definir scoring** — `LEAD_SCORING.md`.
4. **Crear CRM base** — Google Sheet con columnas: ID, Empresa, Ciudad, Web, Tel, Email, DM_Nombre, DM_Cargo, Score, Estado, Fuente, Notas.
5. **Prueba piloto** — 10 veterinarias en una ciudad, medir precisión y cobertura.
6. **Post-prueba** — decidir si LeadMagic agrega valor real vs Apollo/Nimble.

---

## 9. Herramientas pendientes de verificación

Antes de construir el agente, Fidel debe confirmar:
- [ ] ¿Está Nimble autenticado en claude.ai? (Settings → Connectors)
- [ ] ¿Está Apollo autenticado? (Puede requerir API key separada)
- [ ] ¿Tiene cuenta en Bright Data? (para brightdata-plugin)
- [ ] ¿Prefiere Google Sheets como CRM inicial o HubSpot?
- [ ] ¿Qué ciudades/países son el mercado inicial de veterinarias?
