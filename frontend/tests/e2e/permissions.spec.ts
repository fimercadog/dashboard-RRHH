/**
 * permissions.spec.ts — verifica que un usuario sin employees.manage
 * no puede crear ni editar empleados.
 *
 * Corre sin storageState (proyecto "chromium-permissions"):
 * inicia sesión como "Empleado" en cada test.
 */
import { test, expect } from "@playwright/test";

test.describe("Control de permisos — rol Empleado", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/login");

    // Demo mode: tarjeta Laura Medina (rol Empleado)
    await page.getByRole("button", { name: /Laura Medina/i }).click();
    await page.getByRole("button", { name: /Entrar al panel/i }).click();
    await expect(page).toHaveURL(/\/app\/dashboard/, { timeout: 15_000 });
  });

  test("el usuario Empleado no ve el botón Nuevo empleado", async ({ page }) => {
    await page.goto("/app/empleados");

    const url = page.url();
    if (url.includes("/empleados")) {
      await expect(page.getByRole("button", { name: /nuevo empleado/i })).not.toBeVisible();
    } else {
      // Fue redirigido (403, dashboard, etc.) — también pasa
      expect(url).not.toContain("/empleados");
    }
  });

  test("el usuario Empleado no ve el botón Editar en la tabla", async ({ page }) => {
    await page.goto("/app/empleados");
    const url = page.url();
    if (url.includes("/empleados")) {
      await expect(page.getByRole("button", { name: /editar/i })).not.toBeVisible();
    } else {
      expect(url).not.toContain("/empleados");
    }
  });
});
