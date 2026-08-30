import Link from "next/link";
import { navProduct, navSolutions } from "./marketing-data";

export function MarketingFooter() {
  return (
    <footer className="bg-navy text-white">
      <div className="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-4 lg:px-8">
        <div>
          <p className="text-lg font-bold">DFC Talento Humano</p>
          <p className="mt-3 text-sm leading-6 text-white/70">More than recruitment. Complete people management para empresas que quieren ordenar RRHH sin hacerlo pesado.</p>
        </div>
        <div>
          <p className="font-semibold">Producto</p>
          <div className="mt-3 space-y-2">{navProduct.slice(0, 5).map(([label, href]) => <Link className="block text-sm text-white/70" key={href} href={href}>{label}</Link>)}</div>
        </div>
        <div>
          <p className="font-semibold">Soluciones</p>
          <div className="mt-3 space-y-2">{navSolutions.map(([label, href]) => <Link className="block text-sm text-white/70" key={href} href={href}>{label}</Link>)}</div>
        </div>
        <div>
          <p className="font-semibold">Legal</p>
          <div className="mt-3 space-y-2">
            <Link className="block text-sm text-white/70" href="/privacidad">Politica de datos</Link>
            <Link className="block text-sm text-white/70" href="/terminos">Terminos y condiciones</Link>
            <Link className="block text-sm text-white/70" href="/contacto">Contacto</Link>
          </div>
        </div>
      </div>
      <div className="border-t border-white/10 py-5 text-center text-xs text-white/60">© 2026 DFC Talento Humano. Sitio publico y plataforma privada separados.</div>
    </footer>
  );
}
