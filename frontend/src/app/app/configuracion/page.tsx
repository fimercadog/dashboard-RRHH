import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

export default function AppSettingsPage() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold">Configuracion</h1>
        <p className="text-sm text-muted-foreground">Empresa, preferencias, apariencia y parametros generales de RRHH.</p>
      </div>
      <div className="grid gap-4 md:grid-cols-2">
        {["Empresa", "Preferencias", "Apariencia", "RRHH"].map((item) => (
          <Card key={item}><CardHeader><CardTitle>{item}</CardTitle></CardHeader><CardContent className="text-sm text-muted-foreground">Seccion preparada para formularios de configuracion.</CardContent></Card>
        ))}
      </div>
    </div>
  );
}
