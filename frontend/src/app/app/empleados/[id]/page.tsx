import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

export default async function EmployeeProfilePage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Perfil de empleado #{id}</h1>
          <p className="text-sm text-muted-foreground">Ficha preparada para resumen, datos personales, documentos y trazabilidad.</p>
        </div>
        <Badge>Parcial</Badge>
      </div>
      <div className="grid gap-4 lg:grid-cols-3">
        {["Resumen", "Datos personales", "Datos laborales", "Documentos", "Asistencia", "Vacaciones", "Permisos", "Incapacidades", "Novedades"].map((tab) => (
          <Card key={tab}>
            <CardHeader><CardTitle>{tab}</CardTitle></CardHeader>
            <CardContent className="text-sm text-muted-foreground">Seccion lista para conectar detalle incremental del API.</CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
