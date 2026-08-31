"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { ChevronDown, Menu, X } from "lucide-react";
import { useEffect, useState } from "react";
import { Logo } from "@/components/brand/logo";
import { Button } from "@/components/ui/button";
import { navProduct, navSolutions } from "./marketing-data";

function Dropdown({ label, items }: { label: string; items: string[][] }) {
  return (
    <div className="group relative">
      <button className="flex h-10 items-center gap-1 text-sm font-medium text-navy">
        {label} <ChevronDown className="h-4 w-4 transition-transform duration-200 group-hover:rotate-180" />
      </button>
      <div className="invisible absolute left-0 top-10 z-40 w-72 translate-y-2 rounded-2xl border border-border bg-white p-3 opacity-0 shadow-(--marketing-shadow) transition-all group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">
        {items.map(([itemLabel, href]) => (
          <Link key={href} href={href} className="block rounded-xl px-3 py-2 text-sm text-muted-foreground hover:bg-accent hover:text-navy">
            {itemLabel}
          </Link>
        ))}
      </div>
    </div>
  );
}

export function MarketingHeader() {
  const [open, setOpen] = useState(false);
  const pathname = usePathname();
  const links = [
    ["Reclutamiento", "/reclutamiento"],
    ["Precios", "/precios"],
    ["Recursos", "/blog"],
    ["Nosotros", "/nosotros"],
    ["Contacto", "/contacto"],
  ];

  // Cierra el menu movil al cambiar de ruta.
  useEffect(() => {
    setOpen(false);
  }, [pathname]);

  return (
    <header className="sticky top-0 z-50 border-b border-border/80 bg-white/90 backdrop-blur">
      <div className="relative z-50 mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <Link className="shrink-0" href="/">
          <Logo size="lg" />
        </Link>
        <nav className="hidden items-center gap-6 xl:flex">
          <Dropdown label="Producto" items={navProduct} />
          <Dropdown label="Soluciones" items={navSolutions} />
          {links.map(([label, href]) => (
            <Link className="group relative whitespace-nowrap text-sm font-medium text-navy" key={href} href={href}>
              {label}
              <span className="absolute -bottom-1 left-0 h-0.5 w-0 bg-primary transition-all duration-200 group-hover:w-full" />
            </Link>
          ))}
        </nav>
        <div className="hidden items-center gap-3 xl:flex">
          <Link className="inline-flex h-10 items-center justify-center whitespace-nowrap rounded-md px-4 text-sm font-medium text-navy transition-colors hover:bg-muted" href="/login" target="_blank" rel="noopener noreferrer">Iniciar sesion</Link>
          <Link className="inline-flex h-10 items-center justify-center whitespace-nowrap rounded-md bg-primary px-4 text-sm font-medium text-white transition-transform duration-200 hover:scale-105 hover:opacity-90 active:scale-95" href="/demo">Solicitar demo</Link>
        </div>
        <Button
          type="button"
          className="xl:hidden"
          variant="outline"
          size="icon"
          onClick={() => setOpen((value) => !value)}
          aria-label={open ? "Cerrar menu" : "Abrir menu"}
          aria-expanded={open}
        >
          {open ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
        </Button>
      </div>
      {open ? (
        <div className="relative z-50 border-t border-border bg-white px-4 py-4 shadow-lg xl:hidden">
          {[...navProduct, ...navSolutions, ...links].map(([label, href]) => (
            <Link key={href} href={href} className="block rounded-xl px-3 py-3 text-sm font-medium text-navy hover:bg-accent" onClick={() => setOpen(false)}>
              {label}
            </Link>
          ))}
          <div className="mt-3 grid gap-2">
            <Link className="rounded-xl border border-border px-3 py-3 text-center text-sm font-medium" href="/login" target="_blank" rel="noopener noreferrer">Iniciar sesion</Link>
            <Link className="whitespace-nowrap rounded-xl bg-primary px-3 py-3 text-center text-sm font-medium text-white" href="/demo">Solicitar demo</Link>
          </div>
        </div>
      ) : null}
    </header>
  );
}
