/**
 * employees.spec.ts — flujos críticos de empleados.
 * Usa el storageState de auth.setup.ts (usuario Administrador de empresa).
 *
 * Requisitos previos:
 *   - frontend: npm run dev (localhost:3000, NEXT_PUBLIC_DEMO_MODE=true)
 *   - backend: php artisan serve --port=8001 (seeder ejecutado)
 */
import { test, expect, Page } from "@playwright/test";

// CODE es generado una vez por describe.serial — no al nivel de módulo


// Descarta el diálogo "FidelOS — Beta" si aparece al cargar la app.
// El diálogo se muestra después de la hidratación del cliente (~3-5s),
// así que esperamos hasta 6s a que aparezca el botón y lo clickeamos.
async function dismissBetaDialog(page: Page) {
  try {
    await page.getByRole("button", { name: /entendido/i }).click({ timeout: 6_000 });
  } catch {
    // el diálogo no apareció — continuar
  }
}

// ──────────────────────────────────────────────────────────────
// Login y logout
// ──────────────────────────────────────────────────────────────

test.describe("Login y Logout", () => {
  test("redirige al dashboard después del login demo", async ({ page }) => {
    // storageState ya aplicado — solo verificar que el dashboard carga
    await page.goto("/app/dashboard");
    await expect(page).toHaveURL(/\/app\/dashboard/, { timeout: 10_000 });
    // Debe verse algún contenido del dashboard (no la página de login)
    await expect(page.getByRole("heading").first()).toBeVisible();
  });

  test("logout limpia la sesión y vuelve a /login", async ({ page }) => {
    await page.goto("/app/dashboard");

    // Logout: limpiar localStorage manualmente como lo hace clearAuthSession()
    await page.evaluate(() => {
      localStorage.removeItem("hrms_token");
      localStorage.removeItem("hrms_user");
    });

    // Navegar a una ruta protegida — debe redirigir a login
    await page.goto("/app/empleados");
    await expect(page).toHaveURL(/\/login/, { timeout: 10_000 });
  });
});

// ──────────────────────────────────────────────────────────────
// Empleados — tabla
// ──────────────────────────────────────────────────────────────

test.describe("Empleados — tabla", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/app/empleados");
    await dismissBetaDialog(page);
    await expect(page.getByRole("heading", { name: /empleados/i })).toBeVisible({ timeout: 10_000 });
  });

  test("carga la tabla y muestra el botón Nuevo empleado", async ({ page }) => {
    await expect(page.getByRole("button", { name: /nuevo empleado/i })).toBeVisible();
    await expect(page.locator("table")).toBeVisible();
  });
});

// ──────────────────────────────────────────────────────────────
// Empleados — CRUD completo (serial: comparten code entre tests)
// ──────────────────────────────────────────────────────────────

test.describe.serial("Empleados — CRUD", () => {
  // Compartido entre los tres tests del bloque
  let code = "";

  let idNumber = "";

  test.beforeAll(async () => {
    const ts = Date.now();
    code = `E2E-${ts}`;
    idNumber = ts.toString().slice(-9); // 9 dígitos únicos por ejecución
  });

  test("alta: crea un nuevo empleado", async ({ page }) => {
    await page.goto("/app/empleados");
    await dismissBetaDialog(page);
    await page.getByRole("button", { name: /nuevo empleado/i }).click();

    const modal = page.getByRole("dialog");
    await expect(modal).toBeVisible({ timeout: 5_000 });

    await modal.getByLabel(/codigo/i).fill(code);
    await modal.getByLabel(/nombres/i).fill("Test");
    await modal.getByLabel(/apellidos/i).fill("Playwright");
    // selectOption via name attribute (más robusto que getByLabel para selects sin id)
    await modal.locator('select[name="identification_type"]').selectOption("CC");
    await modal.getByLabel(/numero de documento/i).fill(idNumber);
    await modal.getByLabel(/correo/i).fill(`${code}@test.local`);
    await modal.getByLabel(/fecha de ingreso/i).fill("2024-01-15");

    // Verificar que el select fue completado antes de guardar
    await expect(modal.locator('select[name="identification_type"]')).toHaveValue("CC");

    // Interceptar la respuesta para diagnosticar si el problema es cliente o servidor
    const [response] = await Promise.all([
      page.waitForResponse(
        (resp) => resp.url().includes("/employees") && resp.request().method() === "POST",
        { timeout: 10_000 },
      ).catch(() => null),
      modal.getByRole("button", { name: /guardar|crear/i }).click(),
    ]);

    if (!response) {
      // El form nunca llegó al backend — validación cliente falló o error de red
      // Capturar el estado del formulario para diagnóstico
      const selectVal = await modal.locator('select[name="identification_type"]').inputValue().catch(() => "ERROR");
      const empCode = await modal.getByLabel(/codigo/i).inputValue().catch(() => "ERROR");
      throw new Error(`No se envió POST /employees. Select value="${selectVal}", code="${empCode}". El formulario pudo haber fallado validación cliente.`);
    }

    expect(response.status(), `Backend respondió ${response.status()}: ${await response.text().catch(() => "")}`).toBe(201);

    await expect(modal).not.toBeVisible({ timeout: 10_000 });
    // Usar getByRole cell con exact para evitar que coincida también con el email
    await expect(page.getByRole("cell", { name: code, exact: true })).toBeVisible({ timeout: 10_000 });
  });

  test("edición: edita el empleado recién creado", async ({ page }) => {
    await page.goto("/app/empleados");
    await dismissBetaDialog(page);

    await page.getByPlaceholder(/buscar/i).fill(code);
    // Esperar que la búsqueda de backend retorne resultados
    await expect(page.getByRole("cell", { name: code, exact: true })).toBeVisible({ timeout: 10_000 });

    await page.getByRole("button", { name: /editar/i }).first().click();

    const modal = page.getByRole("dialog");
    await expect(modal).toBeVisible({ timeout: 5_000 });

    const firstNameInput = modal.getByLabel(/nombres/i);
    await firstNameInput.clear();
    await firstNameInput.fill("TestEditado");

    await modal.getByRole("button", { name: /guardar|actualizar/i }).click();

    await expect(modal).not.toBeVisible({ timeout: 10_000 });
    await expect(page.getByText("TestEditado")).toBeVisible({ timeout: 10_000 });
  });

  test("baja lógica: desactiva el empleado E2E", async ({ page }) => {
    await page.goto("/app/empleados");
    await dismissBetaDialog(page);
    await page.getByPlaceholder(/buscar/i).fill(code);
    await expect(page.getByRole("cell", { name: code, exact: true })).toBeVisible({ timeout: 10_000 });

    const toggleBtn = page.getByRole("button", { name: /deshabilitar/i }).first();
    await expect(toggleBtn).toBeVisible({ timeout: 5_000 });
    await toggleBtn.click();
    // getByText(/inactivo/i) resolvía al <option> oculto del select de filtros;
    // restringimos al <span> dentro de una celda de tabla para apuntar al badge.
    await expect(
      page.locator("td span", { hasText: /^Inactivo$/i }).first()
    ).toBeVisible({ timeout: 10_000 });
  });
});

// ──────────────────────────────────────────────────────────────
// Exportaciones
// ──────────────────────────────────────────────────────────────

test.describe("Exportaciones", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/app/empleados");
    await dismissBetaDialog(page);
    await expect(page.getByRole("heading", { name: /empleados/i })).toBeVisible({ timeout: 10_000 });
  });

  test("CSV: genera petición autenticada al backend", async ({ page }) => {
    const [request] = await Promise.all([
      page.waitForRequest((req) => req.url().includes("/exports/employees.csv")),
      page.getByRole("button", { name: /^csv$/i }).click(),
    ]);

    expect(request.headers()["authorization"]).toMatch(/^Bearer /);
  });

  test("PDF: genera petición autenticada al backend", async ({ page }) => {
    const [request] = await Promise.all([
      page.waitForRequest((req) => req.url().includes("/exports/employees.pdf")),
      page.getByRole("button", { name: /^pdf$/i }).click(),
    ]);

    expect(request.headers()["authorization"]).toMatch(/^Bearer /);
  });
});
