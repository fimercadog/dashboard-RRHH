/**
 * auth.setup.ts — guarda el storageState de un admin para reutilizarlo
 * en todos los tests del proyecto "chromium".
 *
 * Requisito: frontend corriendo en localhost:3000 con NEXT_PUBLIC_DEMO_MODE=true
 * y backend corriendo en localhost:8001 con el seeder ejecutado.
 */
import { test as setup, expect } from "@playwright/test";
import path from "path";

const AUTH_FILE = path.join(__dirname, ".auth", "admin.json");

setup("autenticarse como Administrador de empresa", async ({ page }) => {
  await page.goto("/login");

  // Demo mode: selector de usuarios por tarjetas
  await page.getByRole("button", { name: /Sofia Mercado/i }).click();

  await page.getByRole("button", { name: /Entrar al panel/i }).click();

  // Esperar redirección a dashboard
  await expect(page).toHaveURL(/\/app\/dashboard/, { timeout: 15_000 });

  // Guardar cookies + localStorage para el resto de los tests
  await page.context().storageState({ path: AUTH_FILE });
});
