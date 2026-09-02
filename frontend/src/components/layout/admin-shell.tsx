"use client";

import * as React from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import {
  Activity,
  BadgeCheck,
  BarChart3,
  Bot,
  BriefcaseBusiness,
  Building2,
  CalendarDays,
  ClipboardList,
  Clock3,
  FileText,
  LayoutDashboard,
  Lock,
  LogOut,
  Menu,
  MessageSquarePlus,
  Moon,
  Settings,
  Shield,
  ShieldAlert,
  Stethoscope,
  Sun,
  UserCircle,
  Users,
  WifiOff,
  X,
  type LucideIcon,
} from "lucide-react";
import { LogoMark } from "@/components/brand/logo";
import { BetaNotice } from "@/components/layout/beta-notice";
import { ContingencyBanner } from "@/components/layout/contingency-banner";
import { useContingency } from "@/lib/contingency/context";
import { openFeedbackForm } from "@/lib/feedback";
import { Button } from "@/components/ui/button";
import { useTheme } from "@/components/theme-provider";
import { api } from "@/lib/api";
import {
  AUTH_EXPIRED_EVENT,
  AuthUser,
  clearAuthSession,
  getAuthToken,
  getStoredUser,
  hasAnyPermission,
  updateStoredUser,
} from "@/lib/auth";
import { cn } from "@/lib/utils";

// Una sola peticion /auth/me compartida: si el efecto se monta dos veces
// (StrictMode en dev, o doble render) no se dispara el request dos veces.
let meRequest: Promise<{ data: { user: AuthUser } }> | null = null;
function fetchMe() {
  meRequest ??= api
    .get<{ user: AuthUser }>("/auth/me")
    .finally(() => {
      meRequest = null;
    });
  return meRequest;
}

type NavItem = {
  href: string;
  label: string;
  icon: LucideIcon;
  permissions?: string[];
  premium?: boolean;
  /** Control critico: color de alerta fijo aunque este inactivo. */
  alert?: boolean;
};

const mainNav: NavItem[] = [
  { href: "/app/dashboard", label: "Dashboard", icon: LayoutDashboard, permissions: ["dashboard.view"] },
  { href: "/app/empleados", label: "Empleados", icon: Users, permissions: ["employees.manage"] },
  { href: "/app/asistencia", label: "Asistencia", icon: Clock3, permissions: ["attendance.manage"] },
  { href: "/app/vacaciones", label: "Vacaciones", icon: CalendarDays, permissions: ["requests.approve"] },
  { href: "/app/permisos", label: "Permisos", icon: BadgeCheck, permissions: ["requests.approve"] },
  { href: "/app/incapacidades", label: "Incapacidades", icon: Stethoscope, permissions: ["requests.approve"] },
  { href: "/app/documentos", label: "Documentos", icon: FileText, permissions: ["documents.manage"] },
  { href: "/app/turnos", label: "Turnos", icon: Activity, permissions: ["attendance.manage"] },
  { href: "/app/reportes", label: "Reportes", icon: BarChart3, permissions: ["reports.view"] },
  { href: "/app/contingencia", label: "Modo contingencia", icon: WifiOff, alert: true },
  { href: "/app/ia", label: "IA para RRHH", icon: Bot, premium: true },
];

const adminNav: NavItem[] = [
  { href: "/app/organizacion", label: "Organizacion", icon: Building2, permissions: ["settings.manage"] },
  { href: "/app/reclutamiento", label: "Reclutamiento", icon: BriefcaseBusiness, permissions: ["employees.manage"] },
  { href: "/app/auditoria", label: "Auditoria", icon: ClipboardList, permissions: ["audit.view"] },
  { href: "/app/usuarios", label: "Usuarios", icon: UserCircle, permissions: ["users.manage"] },
  { href: "/app/roles", label: "Roles", icon: Shield, permissions: ["roles.manage"] },
  { href: "/app/configuracion", label: "Configuracion", icon: Settings, permissions: ["settings.manage"] },
];

function PremiumBadge() {
  return (
    <span className="ml-auto flex items-center gap-1 rounded bg-primary/10 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-primary">
      <Lock className="h-3 w-3" /> Premium
    </span>
  );
}

function NavLink({ item }: { item: NavItem }) {
  const pathname = usePathname();
  const Icon = item.icon;
  const { isActive: contingencyActive, pendingCount } = useContingency();

  if (item.alert) {
    const active = pathname === item.href;
    return (
      <Link
        href={item.href}
        className={cn(
          "flex h-9 items-center gap-3 rounded-md px-3 text-sm font-medium text-warning transition-colors hover:bg-warning/10",
          active && "bg-warning/10",
        )}
      >
        <Icon className="h-4 w-4" />
        <span>{item.label}</span>
        {contingencyActive ? (
          <span className="ml-auto rounded bg-warning/20 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide">
            Activo{pendingCount ? ` · ${pendingCount}` : ""}
          </span>
        ) : null}
      </Link>
    );
  }

  if (item.premium) {
    return (
      <div
        aria-disabled
        title="Disponible en el plan Premium"
        className="flex h-9 cursor-not-allowed items-center gap-3 rounded-md px-3 text-sm text-muted-foreground/60"
      >
        <Icon className="h-4 w-4" />
        <span>{item.label}</span>
        <PremiumBadge />
      </div>
    );
  }

  const active = pathname === item.href;

  return (
    <Link
      href={item.href}
      className={cn(
        "flex h-9 items-center gap-3 rounded-md px-3 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground",
        active && "bg-accent text-foreground",
      )}
    >
      <Icon className="h-4 w-4" />
      <span>{item.label}</span>
    </Link>
  );
}

export function AdminShell({ children }: { children: React.ReactNode }) {
  const { theme, setTheme } = useTheme();
  const router = useRouter();
  const pathname = usePathname();
  // Arranca en null: el server no tiene localStorage, sembrar el estado desde
  // getStoredUser() en el render inicial rompe la hidratacion (React #418).
  // El usuario cacheado se carga en el efecto, ya en cliente.
  const [user, setUser] = React.useState<AuthUser | null>(null);
  const [checkingSession, setCheckingSession] = React.useState(true);
  const [mobileNavOpen, setMobileNavOpen] = React.useState(false);

  // Cierra el menu movil al navegar a otra ruta.
  React.useEffect(() => {
    setMobileNavOpen(false);
  }, [pathname]);

  const logout = React.useCallback(async () => {
    try {
      await api.post("/auth/logout");
    } catch {
      // The local session should still be cleared if the token is already invalid.
    } finally {
      clearAuthSession();
      router.replace("/login");
    }
  }, [router]);

  React.useEffect(() => {
    const storedToken = getAuthToken();

    if (!storedToken) {
      router.replace("/login");
      return;
    }

    // Pinta ya desde la cache (cliente), sin esperar a /auth/me.
    const cached = getStoredUser();
    if (cached) {
      setUser(cached);
      setCheckingSession(false);
    }

    fetchMe()
      .then((response) => {
        updateStoredUser(response.data.user);
        setUser(response.data.user);
      })
      .catch(() => {
        // El interceptor 401 ya limpio la sesion; aqui solo redirigimos.
        clearAuthSession();
        router.replace("/login");
      })
      .finally(() => setCheckingSession(false));
  }, [router]);

  React.useEffect(() => {
    function handleExpiredSession() {
      clearAuthSession();
      router.replace("/login");
    }

    window.addEventListener(AUTH_EXPIRED_EVENT, handleExpiredSession);
    return () => window.removeEventListener(AUTH_EXPIRED_EVENT, handleExpiredSession);
  }, [router]);

  const visibleMainNav = mainNav.filter((item) => hasAnyPermission(user, item.permissions));
  const visibleAdminNav = adminNav.filter((item) => hasAnyPermission(user, item.permissions));

  // Guard por ruta: si la ruta actual corresponde a un modulo del menu y el
  // usuario no tiene su permiso, se muestra una pantalla de acceso denegado.
  // El backend igual responde 403; esto es UX (evita tabla rota + 403 en rojo).
  const activeNav = [...mainNav, ...adminNav]
    .filter((item) => pathname === item.href || pathname.startsWith(`${item.href}/`))
    .sort((a, b) => b.href.length - a.href.length)[0];
  const authorized = !user || !activeNav || hasAnyPermission(user, activeNav.permissions);

  if (checkingSession && !user) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-background text-foreground">
        <div className="text-center">
          <p className="text-sm font-medium">Validando sesion</p>
          <p className="mt-1 text-xs text-muted-foreground">Preparando el panel privado...</p>
        </div>
      </div>
    );
  }

  const sidebarHeader = (
    <div className="flex h-16 items-center gap-3 border-b border-border px-5">
      <LogoMark size="sm" />
      <div>
        <p className="flex items-center gap-1.5 text-sm font-semibold text-foreground">
          DFC
          <span className="rounded bg-warning/15 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-warning">
            Beta
          </span>
        </p>
        <p className="text-xs text-muted-foreground">Talento Humano</p>
      </div>
    </div>
  );

  const navBody = (
    <nav className="flex-1 space-y-6 overflow-y-auto p-4">
      <div className="space-y-1">{visibleMainNav.map((item) => <NavLink key={item.href} item={item} />)}</div>
      {visibleAdminNav.length ? (
        <div>
          <p className="mb-2 px-3 text-xs font-medium uppercase text-muted-foreground">Administracion</p>
          <div className="space-y-1">{visibleAdminNav.map((item) => <NavLink key={item.href} item={item} />)}</div>
        </div>
      ) : null}
    </nav>
  );

  return (
    <div className="min-h-screen bg-background text-foreground">
      <aside className="fixed inset-y-0 left-0 hidden w-72 border-r border-border bg-card lg:flex lg:flex-col">
        {sidebarHeader}
        {navBody}
      </aside>

      {mobileNavOpen ? (
        <div className="fixed inset-0 z-40 lg:hidden">
          <div
            className="absolute inset-0 bg-black/50"
            onClick={() => setMobileNavOpen(false)}
            aria-hidden
          />
          <div className="absolute inset-y-0 left-0 flex w-72 max-w-[82%] flex-col border-r border-border bg-card">
            <div className="relative">
              {sidebarHeader}
              <Button
                variant="outline"
                size="icon"
                aria-label="Cerrar menu"
                title="Cerrar menu"
                onClick={() => setMobileNavOpen(false)}
                className="absolute right-3 top-1/2 -translate-y-1/2"
              >
                <X className="h-4 w-4" />
              </Button>
            </div>
            {navBody}
          </div>
        </div>
      ) : null}

      <div className="lg:pl-72">
        <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-border bg-background/95 px-4 backdrop-blur lg:px-6">
          <div className="flex items-center gap-3">
            <Button
              variant="outline"
              size="icon"
              className="lg:hidden"
              aria-label="Abrir menu"
              title="Abrir menu"
              aria-expanded={mobileNavOpen}
              onClick={() => setMobileNavOpen(true)}
            >
              <Menu className="h-4 w-4" />
            </Button>
            <div>
              <p className="text-sm font-medium">{user?.company?.name ?? "DFC Talento Humano"}</p>
              <p className="text-xs text-muted-foreground">Panel privado de Recursos Humanos</p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <div className="hidden items-center gap-2 rounded-md border border-border bg-card px-3 py-2 sm:flex">
              <UserCircle className="h-4 w-4 text-primary" />
              <div className="min-w-0">
                <p className="truncate text-xs font-medium">{user?.name ?? "Usuario"}</p>
                <p className="truncate text-[11px] text-muted-foreground">{user?.roles?.[0] ?? user?.email}</p>
              </div>
            </div>
            <BetaNotice />
            <Button
              variant="outline"
              size="icon"
              aria-label="Enviar un comentario"
              title="Enviar un comentario"
              onClick={openFeedbackForm}
              className="border-primary/40 bg-primary/10 text-primary hover:bg-primary/20"
            >
              <MessageSquarePlus className="h-4 w-4" />
            </Button>
            <Button
              variant="outline"
              size="icon"
              aria-label="Cambiar tema"
              title={theme === "dark" ? "Cambiar a tema claro" : "Cambiar a tema oscuro"}
              onClick={() => setTheme(theme === "dark" ? "light" : "dark")}
            >
              <Sun className="h-4 w-4 dark:hidden" />
              <Moon className="hidden h-4 w-4 dark:block" />
            </Button>
            <Button variant="outline" size="icon" aria-label="Cerrar sesion" title="Cerrar sesion" onClick={logout}>
              <LogOut className="h-4 w-4" />
            </Button>
          </div>
        </header>
        <ContingencyBanner />
        <main className="mx-auto w-full max-w-7xl px-4 py-6 lg:px-6">
          {authorized ? (
            children
          ) : (
            <div className="flex min-h-[60vh] flex-col items-center justify-center text-center">
              <ShieldAlert className="h-10 w-10 text-muted-foreground" />
              <h1 className="mt-4 text-lg font-semibold">No tienes acceso a esta seccion</h1>
              <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                Tu rol no incluye los permisos necesarios. Si crees que es un error, contacta a un administrador.
              </p>
              <Link href="/app/dashboard" className="mt-6 inline-flex h-9 items-center rounded-md bg-primary px-4 text-sm font-medium text-white">
                Ir al dashboard
              </Link>
            </div>
          )}
        </main>
      </div>
    </div>
  );
}
