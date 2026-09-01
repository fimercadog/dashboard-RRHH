"use client";

import * as React from "react";

// Anima un numero de 0 al valor final al montar. Respeta prefers-reduced-motion.
export function useCountUp(target: number, durationMs = 800) {
  const [value, setValue] = React.useState(target);

  React.useEffect(() => {
    if (typeof window === "undefined") return;
    // value ya arranca en `target` (SSR / sin JS lo muestra bien); si no hay
    // animacion, no hay nada que hacer.
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
    let raf = 0;
    let start = 0;
    // El primer tick (t=0) pone el valor en ~0 y de ahi rampa: no hace falta un
    // setState sincrono en el cuerpo del efecto.
    const tick = (now: number) => {
      start ||= now;
      const t = Math.min(1, (now - start) / durationMs);
      const eased = 1 - Math.pow(1 - t, 3);
      setValue(target * eased);
      if (t < 1) raf = requestAnimationFrame(tick);
    };
    raf = requestAnimationFrame(tick);
    return () => cancelAnimationFrame(raf);
  }, [target, durationMs]);

  return value;
}
