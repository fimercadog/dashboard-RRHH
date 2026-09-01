"use client";

import * as React from "react";
import { AlertTriangle, CheckCircle2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";

// Se muestra una sola vez por sesion (al iniciar). El boton del header lo
// reabre cuando se quiera, sin depender de recargar sesion.
const SEEN_KEY = "dfc_beta_notice_seen";

export function BetaNotice() {
  const [open, setOpen] = React.useState(false);

  React.useEffect(() => {
    try {
      if (!window.sessionStorage.getItem(SEEN_KEY)) {
        setOpen(true);
        window.sessionStorage.setItem(SEEN_KEY, "1");
      }
    } catch {
      // sessionStorage no disponible (modo privado, etc.): sin popup automatico.
    }
  }, []);

  return (
    <>
      <Button
        variant="outline"
        size="icon"
        aria-label="Acerca de la version beta"
        title="Acerca de la version beta"
        onClick={() => setOpen(true)}
      >
        <AlertTriangle className="h-4 w-4 text-warning" />
      </Button>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="max-w-lg">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <AlertTriangle className="h-5 w-5 text-warning" />
              DFC Talento Humano — Beta
            </DialogTitle>
            <DialogDescription>Estado del panel y que esperar.</DialogDescription>
          </DialogHeader>

          <ul className="space-y-3 text-sm text-foreground">
            <li className="flex gap-2.5">
              <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-success" />
              <span>
                Paso las pruebas de desarrollo: cada modulo, rol y flujo se verifico uno por uno.
              </span>
            </li>
            <li className="flex gap-2.5">
              <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-success" />
              <span>Funciona con datos reales de demostracion, no con contenido de relleno.</span>
            </li>
            <li className="flex gap-2.5">
              <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0 text-warning" />
              <span>
                Al ser una beta, puede tener detalles por pulir. Si encuentras algo raro, avisanos.
              </span>
            </li>
          </ul>

          <DialogFooter>
            <Button onClick={() => setOpen(false)}>Entendido</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}
