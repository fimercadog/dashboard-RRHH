"use client";
import { useEffect } from "react";

// Elimina la clase .dark de <html> si quedó activa al venir del panel admin.
// El ThemeProvider solo corre en /app y /login; al navegar a marketing la clase
// persiste porque nadie la limpia. Este componente la remueve en el cliente.
export function ForceLight() {
  useEffect(() => {
    document.documentElement.classList.remove("dark");
  }, []);
  return null;
}
