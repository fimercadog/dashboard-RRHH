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
  LogOut,
  Menu,
  Moon,
  Settings,
  Shield,
  Stethoscope,
  Sun,
  UserCircle,
  Users,
  X,
} from "lucide-react";
import { LogoMark } from "@/components/brand/logo";
import { Button } from "@/components/ui/button";
import { useTheme } from "@/components/theme-provider";
import { api } from "@/lib/api";
import {
  AUTH_EXPIRED_EVENT,
  AuthUser,
  clearAuthSession,
  getAuthToken,
  hasAnyPermission,
  updateStoredUser,
} from "@/lib/auth";
import { cn } from "@/lib/utils";

const mainNav = [
  { href: "/app/dashboard", label: "Dashboard", icon: LayoutDashboard, permissions: ["dashboard.view"] },
  { href: "/app/empleados", label: "Empleados", icon: Users, permissions: ["employees.manage"] },
  { href: "/app/asistencia", label: "Asistencia", icon: Clock3, permissions: ["attendance.manage"] },
  { href: "/app/vacaciones", label: "Vacaciones", icon: CalendarDays, permissions: ["requests.approve"] },
  { href: "/app/permisos", label: "Permisos", icon: BadgeCheck, permissions: ["requests.approve"] },
  { href: "/app/incapacidades", label: "Incapacidades", icon: Stethoscope, permissions: ["requests.approve"] },
  { href: "/app/documentos", label: "Documentos", icon: FileText, permissions: ["documents.manage"] },
  { href: "/app/turnos", label: "Turnos", icon: Activity, permissions: ["attendance.manage"] },
  { href: "/app/reportes", label: "Reportes", icon: BarChart3, permissions: ["reports.view"] },
  { href: "/app/ia", label: "IA para RRHH", icon: Bot },
];

const adminNav = [
  { href: "/app/organizacion", label: "Organizacion", icon: Building2, permissions: ["settings.manage"] },
  { href: "/app/reclutamiento", label: "Reclutamiento", icon: BriefcaseBusiness, permissions: ["employees.manage"] },
  { href: "/app/auditoria", label: "Auditoria", icon: ClipboardList, permissions: ["audit.view"] },
  { href: "/app/usuarios", label: "Usuarios", icon: UserCircle, permissions: ["users.manage"] },
  { href: "/app/roles", label: "Roles", icon: Shield, permissions: ["roles.manage"] },
  { href: "/app/configuracion", label: "Configuracion", icon: Settings, permissions: ["settings.manage"] },
];

type NavItem = (typeof mainNav)[number] | (typeof adminNav)[number];

function NavLink({ item }: { item: NavItem }) {
  const pathname = usePathname();
  const active = pathname === item.href;
  const Icon = item.icon;

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

    api
      .get<{ user: AuthUser }>("/auth/me")
      .then((response) => {
        updateStoredUser(response.data.user);
        setUser(response.data.user);
      })
      .catch(() => {
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
        <p className="text-sm font-semibold text-foreground">DFC</p>
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
              aria-expanded={mobileNavOpen}
              onClick={() => setMobileNavOpen(true)}
            >
              <Menu className="h-4 w-4" />
            </Button>
            <div>
              <p className="text-sm font-medium">Andes People Solutions</p>
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
            <Button
              variant="outline"
              size="icon"
              aria-label="Cambiar tema"
              onClick={() => setTheme(theme === "dark" ? "light" : "dark")}
            >
              <Sun className="h-4 w-4 dark:hidden" />
              <Moon className="hidden h-4 w-4 dark:block" />
            </Button>
            <Button variant="outline" size="icon" aria-label="Cerrar sesion" onClick={logout}>
              <LogOut className="h-4 w-4" />
            </Button>
          </div>
        </header>
        <main className="mx-auto w-full max-w-7xl px-4 py-6 lg:px-6">{children}</main>
      </div>
    </div>
  );
}
