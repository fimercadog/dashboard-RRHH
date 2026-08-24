import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

export default function AppOrganizationPage() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold">Organizacion</h1>
        <p className="text-sm text-muted-foreground">Catalogos de empresa, departamentos y cargos.</p>
      </div>
      <div className="grid gap-4 md:grid-cols-3">
        {["Empresa", "Departamentos", "Cargos"].map((item) => (
          <Card key={item}><CardHeader><CardTitle>{item}</CardTitle></CardHeader><CardContent className="text-sm text-muted-foreground">CRUD preparado para gestion organizacional.</CardContent></Card>
        ))}
      </div>
    </div>
  );
}
