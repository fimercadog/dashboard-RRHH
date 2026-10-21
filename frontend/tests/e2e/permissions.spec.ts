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

    // La shell NO redirige: permanece en /app/empleados y renderiza pantalla de
    // acceso denegado. Verificamos la URL y ambas expectativas positiva/negativa.
    await expect(page).toHaveURL(/\/app\/empleados/, { timeout: 10_000 });
    await expect(page.getByText(/no tienes acceso/i)).toBeVisible();
    await expect(page.getByRole("button", { name: /nuevo empleado/i })).not.toBeVisible();
  });

  test("el usuario Empleado no ve el botón Editar en la tabla", async ({ page }) => {
    await page.goto("/app/empleados");

    await expect(page).toHaveURL(/\/app\/empleados/, { timeout: 10_000 });
    await expect(page.getByText(/no tienes acceso/i)).toBeVisible();
    await expect(page.getByRole("button", { name: /editar/i })).not.toBeVisible();
  });

  test("la API de empleados devuelve 403 para el rol Empleado", async ({ page }) => {
    // El token Bearer se almacena en localStorage tras el beforeEach (login completado).
    const token = await page.evaluate(() => window.localStorage.getItem("hrms_token"));
    expect(token, "Se esperaba un token en localStorage tras el login").toBeTruthy();

    const response = await page.request.get("http://localhost:8001/api/employees", {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
      },
    });

    expect(response.status()).toBe(403);
  });
});
