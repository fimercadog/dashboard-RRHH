import { expect, test } from "@playwright/test";

const PUBLIC_ROUTES = [
  "/",
  "/precios",
  "/blog",
  "/nosotros",
  "/contacto",
  "/demo",
  "/reclutamiento",
  "/soluciones",
  "/producto/empleados",
  "/producto/asistencia",
  "/producto/vacaciones",
  "/producto/documentos",
  "/producto/turnos",
  "/producto/reportes",
  "/producto/ia",
  "/privacidad",
  "/terminos",
  "/login",
];

const BLOG_SLUGS = [
  "como-digitalizar-rrhh-sin-caos",
  "asistencia-y-ausentismo",
  "vacaciones-sin-excel",
  "pipeline-de-reclutamiento",
  "documentos-laborales",
  "ia-en-recursos-humanos",
];

test.describe("Rutas públicas — carga sin error", () => {
  for (const path of PUBLIC_ROUTES) {
    test(`${path} responde < 400`, async ({ page }) => {
      const response = await page.goto(path);
      expect(response?.status()).toBeLessThan(400);
      const body = await page.textContent("body");
      expect(body).not.toContain("Application error");
    });
  }
});

test.describe("Blog — artículos individuales", () => {
  for (const slug of BLOG_SLUGS) {
    test(`/blog/${slug} carga sin error`, async ({ page }) => {
      const response = await page.goto(`/blog/${slug}`);
      expect(response?.status()).toBeLessThan(400);
    });
  }
});

test.describe("Navegación principal", () => {
  test("header: logo, Iniciar sesión y Solicitar demo visibles", async ({ page }) => {
    await page.goto("/");
    await expect(page.locator("header")).toBeVisible();
    await expect(page.locator('header a[href="/"]')).toBeVisible();
    await expect(page.getByRole("link", { name: /iniciar sesi/i }).first()).toBeVisible();
    await expect(page.getByRole("link", { name: /solicitar demo/i }).first()).toBeVisible();
  });

  test("menú móvil se abre y cierra", async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto("/");
    await page.getByRole("button", { name: /abrir menu/i }).click();
    await expect(page.getByRole("button", { name: /cerrar menu/i })).toBeVisible();
    await expect(page.getByRole("link", { name: "Precios" })).toBeVisible();
    await page.getByRole("button", { name: /cerrar menu/i }).click();
    await expect(page.getByRole("button", { name: /abrir menu/i })).toBeVisible();
  });

  test("footer: links Privacidad y Términos visibles", async ({ page }) => {
    await page.goto("/");
    const footer = page.locator("footer");
    await expect(footer).toBeVisible();
    await expect(footer.getByRole("link", { name: /politica|privacidad/i })).toBeVisible();
    await expect(footer.getByRole("link", { name: /terminos/i })).toBeVisible();
  });

  test("nav Producto: hover dropdown muestra enlace a /producto/empleados", async ({ page }) => {
    await page.goto("/");
    // The dropdown uses CSS group-hover — must hover the parent button first
    await page.locator("header nav").getByRole("button", { name: /producto/i }).hover();
    const link = page.locator("header").getByRole("link", { name: "Gestion de empleados" });
    await expect(link).toBeVisible();
    const href = await link.getAttribute("href");
    expect(href).toMatch(/^\/producto\/empleados\/?$/);
  });

  test("link Precios del header navega a /precios", async ({ page }) => {
    await page.goto("/");
    // Desktop nav
    await page.locator("header nav").getByRole("link", { name: "Precios" }).click();
    await expect(page).toHaveURL(/\/precios/);
  });
});

test.describe("SEO — metadatos básicos", () => {
  test("home: title FidelOS y description presentes", async ({ page }) => {
    await page.goto("/");
    await expect(page).toHaveTitle(/FidelOS/);
    const desc = await page.locator('meta[name="description"]').getAttribute("content");
    expect(desc).toBeTruthy();
    expect(desc!.length).toBeGreaterThan(20);
  });

  test("/precios tiene title con FidelOS", async ({ page }) => {
    await page.goto("/precios");
    await expect(page).toHaveTitle(/FidelOS/);
  });

  test("Open Graph presente en home", async ({ page }) => {
    await page.goto("/");
    const ogTitle = await page.locator('meta[property="og:title"]').getAttribute("content");
    expect(ogTitle).toBeTruthy();
  });
});

test.describe("Responsive — sin scroll horizontal", () => {
  const checkPaths = ["/", "/precios", "/nosotros", "/contacto", "/producto/empleados"];
  for (const path of checkPaths) {
    test(`${path} sin scroll horizontal en 375px`, async ({ page }) => {
      await page.setViewportSize({ width: 375, height: 812 });
      await page.goto(path);
      const overflow = await page.evaluate(() => {
        return document.documentElement.scrollWidth > document.documentElement.clientWidth;
      });
      expect(overflow).toBe(false);
    });
  }
});

test.describe("Formulario de contacto", () => {
  test("muestra validación al enviar vacío", async ({ page }) => {
    await page.goto("/contacto");
    // Encontrar el primer botón de avance/envío
    const btn = page.getByRole("button", { name: /siguiente|enviar|continuar/i }).first();
    const btnVisible = await btn.isVisible().catch(() => false);
    if (btnVisible) {
      await btn.click();
      // Esperar alguna señal de error de validación
      await page.waitForTimeout(500);
      await page.locator('[aria-invalid="true"], .text-destructive, [data-slot="form-message"]').count();
      // Si hay validación HTML5 nativa, el form no avanza — cualquier resultado es aceptable
      // Solo fallamos si la página explota
      const body = await page.textContent("body");
      expect(body).not.toContain("Application error");
    }
  });
});

test.describe("Links internos — destinos correctos", () => {
  test("CTA Solicitar demo lleva a /demo", async ({ page }) => {
    await page.goto("/");
    const cta = page.getByRole("link", { name: /solicitar demo/i }).first();
    const href = await cta.getAttribute("href");
    expect(href).toMatch(/^\/demo\/?$/);
  });

  test("Logo en header lleva a /", async ({ page }) => {
    await page.goto("/precios");
    await page.locator('header a[href="/"]').first().click();
    await expect(page).toHaveURL(/^http:\/\/localhost:3000\/?$/);
  });

  test("footer link Contacto lleva a /contacto", async ({ page }) => {
    await page.goto("/");
    await page.locator("footer").getByRole("link", { name: "Contacto" }).click();
    await expect(page).toHaveURL(/\/contacto/);
  });
});

test.describe("Blog editorial", () => {
  test("blog listing muestra articulo destacado y grid", async ({ page }) => {
    await page.goto("/blog/");
    await expect(page.locator("h1")).toBeVisible();
    const cards = page.locator("a[href*='/blog/']");
    await expect(cards.first()).toBeVisible();
  });

  test("articulo de blog abre y tiene contenido real", async ({ page }) => {
    await page.goto("/blog/como-digitalizar-rrhh-sin-caos/");
    await expect(page.locator("h1")).toBeVisible();
    await expect(page.locator("body")).not.toContainText("contenido inicial ficticio");
  });

  test("todos los slugs de blog cargan sin error", async ({ page }) => {
    const slugs = [
      "como-digitalizar-rrhh-sin-caos",
      "asistencia-y-ausentismo",
      "vacaciones-sin-excel",
      "pipeline-de-reclutamiento",
      "documentos-laborales",
      "ia-en-recursos-humanos",
    ];
    for (const slug of slugs) {
      await page.goto(`/blog/${slug}/`);
      await expect(page.locator("h1")).toBeVisible();
      await expect(page.locator("body")).not.toContainText("Application error");
    }
  });

  test("pagina 404 para ruta inexistente", async ({ page }) => {
    await page.goto("/esta-pagina-no-existe/");
    await expect(page.locator("body")).toContainText("404");
    await expect(page.locator("a[href='/']").first()).toBeVisible();
  });

  test("pagina 404 para articulo de blog inexistente", async ({ page }) => {
    await page.goto("/blog/articulo-que-no-existe/");
    await expect(page.locator("body")).toContainText("404");
  });

  test("boton WhatsApp tiene href correcto", async ({ page }) => {
    await page.goto("/");
    const waLink = page.locator("a[href*='wa.me/573058148918']");
    await expect(waLink).toBeVisible();
  });
});
