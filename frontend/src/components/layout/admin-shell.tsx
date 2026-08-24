"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
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
  Menu,
  Moon,
  Settings,
  Shield,
  Stethoscope,
  Sun,
  Users,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { useTheme } from "@/components/theme-provider";
import { cn } from "@/lib/utils";

const mainNav = [
  { href: "/app/dashboard", label: "Dashboard", icon: LayoutDashboard },
  { href: "/app/empleados", label: "Empleados", icon: Users },
  { href: "/app/asistencia", label: "Asistencia", icon: Clock3 },
  { href: "/app/vacaciones", label: "Vacaciones", icon: CalendarDays },
  { href: "/app/permisos", label: "Permisos", icon: BadgeCheck },
  { href: "/app/incapacidades", label: "Incapacidades", icon: Stethoscope },
  { href: "/app/documentos", label: "Documentos", icon: FileText },
  { href: "/app/turnos", label: "Turnos", icon: Activity },
  { href: "/app/reportes", label: "Reportes", icon: BarChart3 },
  { href: "/app/ia", label: "IA para RRHH", icon: Bot },
];

const adminNav = [
  { href: "/app/organizacion", label: "Organizacion", icon: Building2 },
  { href: "/app/reclutamiento", label: "Reclutamiento", icon: BriefcaseBusiness },
  { href: "/app/auditoria", label: "Auditoria", icon: ClipboardList },
  { href: "/app/roles", label: "Roles", icon: Shield },
  { href: "/app/configuracion", label: "Configuracion", icon: Settings },
];

function NavLink({ item }: { item: (typeof mainNav)[number] }) {
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

  return (
    <div className="min-h-screen bg-background text-foreground">
      <aside className="fixed inset-y-0 left-0 hidden w-72 border-r border-border bg-card lg:flex lg:flex-col">
        <div className="flex h-16 items-center border-b border-border px-5">
          <div>
            <p className="text-sm font-semibold text-primary">FidelOS</p>
            <p className="text-xs text-muted-foreground">HRMS administrativo</p>
          </div>
        </div>
        <nav className="flex-1 space-y-6 overflow-y-auto p-4">
          <div className="space-y-1">{mainNav.map((item) => <NavLink key={item.href} item={item} />)}</div>
          <div>
            <p className="mb-2 px-3 text-xs font-medium uppercase text-muted-foreground">Administracion</p>
            <div className="space-y-1">{adminNav.map((item) => <NavLink key={item.href} item={item} />)}</div>
          </div>
        </nav>
      </aside>
      <div className="lg:pl-72">
        <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-border bg-background/95 px-4 backdrop-blur lg:px-6">
          <div className="flex items-center gap-3">
            <Button variant="outline" size="icon" className="lg:hidden" aria-label="Abrir menu">
              <Menu className="h-4 w-4" />
            </Button>
            <div>
              <p className="text-sm font-medium">Andes People Solutions</p>
              <p className="text-xs text-muted-foreground">Panel privado de Recursos Humanos</p>
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
        </header>
        <main className="mx-auto w-full max-w-7xl px-4 py-6 lg:px-6">{children}</main>
      </div>
    </div>
  );
}
