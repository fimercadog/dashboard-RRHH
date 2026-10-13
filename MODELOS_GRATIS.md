# Modelos Gratuitos para Kilo Code

Todos estos proveedores tienen nivel gratuito sin tarjeta de crédito.
Para cambiar de modelo: abre Kilo Code → ícono de engranaje (⚙) → API Provider.

---

## 1. Google Gemini — RECOMENDADO PARA EMPEZAR

**Por qué:** El más capaz gratis. Excelente en código y agentes. Límite generoso.

- Obtener API key: https://aistudio.google.com → "Get API key"
- En Kilo Code:
  - Provider: **Google Gemini**
  - API Key: (pegar la key)
  - Model: `gemini-2.5-flash` o `gemini-2.0-flash`

---

## 2. Groq — EL MÁS RÁPIDO

**Por qué:** Responde en segundos reales. Límite diario de tokens, se renueva cada 24h.

- Obtener API key: https://console.groq.com → "Create API Key"
- En Kilo Code:
  - Provider: **Groq**
  - API Key: (pegar la key)
  - Model: `llama-3.3-70b-versatile` (mejor) o `llama-3.1-8b-instant` (más rápido)

---

## 3. OpenRouter — ACCESO A MUCHOS MODELOS

**Por qué:** Un solo lugar para probar muchos modelos. Algunos completamente gratis.

- Obtener API key: https://openrouter.ai → "Sign In" → "Keys"
- En Kilo Code:
  - Provider: **OpenRouter**
  - API Key: (pegar la key)
  - Models gratuitos que funcionan bien:
    - `google/gemini-2.0-flash-exp:free`
    - `meta-llama/llama-3.1-8b-instruct:free`
    - `qwen/qwen3-14b:free`

---

## 4. Mistral — ALTERNATIVA EUROPEA

**Por qué:** Muy bueno en código. Nivel gratuito con `mistral-small`.

- Obtener API key: https://console.mistral.ai → "API Keys"
- En Kilo Code:
  - Provider: **Mistral**
  - API Key: (pegar la key)
  - Model: `mistral-small-latest`

---

## Orden de prioridad sugerido

1. **Gemini 2.5 Flash** — tareas largas o complejas (más contexto)
2. **Groq llama-3.3-70b** — cuando necesites velocidad
3. **OpenRouter qwen3-14b:free** — si Groq llega al límite diario
4. **Local Qwen 7B** — solo para preguntas rápidas en modo "Ask" (no agente)

---

## Problema con el modelo local (Qwen 7B)

El Qwen 7B falla como agente porque no maneja el protocolo de tool-calling.
Si lo usas, selecciona el perfil **"Ask"** en Kilo Code, no "Agent" ni "Architect".
Para tareas agénticas reales, usa cualquiera de los proveedores web de arriba.
