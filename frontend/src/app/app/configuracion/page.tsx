"use client";

import * as React from "react";
import { toast } from "sonner";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { api } from "@/lib/api";

type Company = {
  name: string;
  nit: string | null;
  email: string | null;
  phone: string | null;
  address: string | null;
  timezone: string | null;
  locale: string | null;
  date_format: string | null;
};

const empresaFields: { name: keyof Company; label: string; type?: string }[] = [
  { name: "name", label: "Razon social" },
  { name: "nit", label: "NIT" },
  { name: "email", label: "Correo", type: "email" },
  { name: "phone", label: "Telefono" },
  { name: "address", label: "Direccion" },
];

const regionalFields: { name: keyof Company; label: string; placeholder: string }[] = [
  { name: "timezone", label: "Zona horaria", placeholder: "America/Bogota" },
  { name: "locale", label: "Idioma", placeholder: "es" },
  { name: "date_format", label: "Formato de fecha", placeholder: "Y-m-d" },
];

export default function AppSettingsPage() {
  const [company, setCompany] = React.useState<Company | null>(null);
  const [saving, setSaving] = React.useState(false);

  React.useEffect(() => {
    api.get<{ data: Company }>("/company").then((res) => setCompany(res.data.data)).catch(() => {});
  }, []);

  function setField(name: keyof Company, value: string) {
    setCompany((prev) => (prev ? { ...prev, [name]: value } : prev));
  }

  async function save(event: React.FormEvent) {
    event.preventDefault();
    if (!company) return;
    setSaving(true);
    try {
      const res = await api.put<{ data: Company }>("/company", company);
      setCompany(res.data.data);
      toast.success("Configuracion guardada");
    } catch {
      toast.error("No se pudo guardar. Revisa los campos.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold">Configuracion</h1>
        <p className="text-sm text-muted-foreground">Datos de la empresa y preferencias regionales. El tema claro/oscuro se cambia desde el boton del encabezado.</p>
      </div>

      {!company ? (
        <p className="text-sm text-muted-foreground">Cargando...</p>
      ) : (
        <form onSubmit={save} className="space-y-6">
          <Card>
            <CardHeader><CardTitle>Empresa</CardTitle></CardHeader>
            <CardContent className="grid gap-4 sm:grid-cols-2">
              {empresaFields.map((field) => (
                <label key={field.name} className="space-y-1 text-sm">
                  <span className="font-medium">{field.label}</span>
                  <Input
                    type={field.type ?? "text"}
                    value={company[field.name] ?? ""}
                    onChange={(e) => setField(field.name, e.target.value)}
                  />
                </label>
              ))}
            </CardContent>
          </Card>

          <Card>
            <CardHeader><CardTitle>Preferencias regionales</CardTitle></CardHeader>
            <CardContent className="grid gap-4 sm:grid-cols-3">
              {regionalFields.map((field) => (
                <label key={field.name} className="space-y-1 text-sm">
                  <span className="font-medium">{field.label}</span>
                  <Input
                    placeholder={field.placeholder}
                    value={company[field.name] ?? ""}
                    onChange={(e) => setField(field.name, e.target.value)}
                  />
                </label>
              ))}
            </CardContent>
          </Card>

          <Button type="submit" disabled={saving}>{saving ? "Guardando..." : "Guardar cambios"}</Button>
        </form>
      )}
    </div>
  );
}
