"use client";

import { Power } from "lucide-react";
import * as React from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { api } from "@/lib/api";

type ToggleStatusActionProps = {
  resource: string;
  id: number | string;
  active: boolean;
  refresh: () => void;
  field?: string;
  activeValue?: string;
  inactiveValue?: string;
};

export function ToggleStatusAction({
  resource,
  id,
  active,
  refresh,
  field = "status",
  activeValue = "active",
  inactiveValue = "inactive",
}: ToggleStatusActionProps) {
  const [loading, setLoading] = React.useState(false);

  async function toggle() {
    setLoading(true);
    try {
      await api.put(`${resource}/${id}`, { [field]: active ? inactiveValue : activeValue });
      toast.success(active ? "Registro deshabilitado" : "Registro habilitado");
      refresh();
    } catch {
      toast.error("No se pudo actualizar el estado.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <Button variant="ghost" size="sm" onClick={toggle} disabled={loading}>
      <Power className="h-4 w-4" /> {active ? "Deshabilitar" : "Habilitar"}
    </Button>
  );
}
