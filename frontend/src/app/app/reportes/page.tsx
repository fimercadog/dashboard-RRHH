import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

const reports = ["Empleados activos", "Altas", "Bajas", "Asistencia", "Tardanzas", "Ausentismo", "Vacaciones", "Permisos", "Incapacidades", "Documentos vencidos", "Contratos proximos", "Distribucion por area", "Distribucion por cargo"];

export default function ReportsPage() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold">Reportes</h1>
        <p className="text-sm text-muted-foreground">Catalogo inicial de reportes. Los reportes tabulares se conectaran a TanStack Table por modulo.</p>
      </div>
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {reports.map((report) => (
          <Card key={report}>
            <CardHeader><CardTitle>{report}</CardTitle></CardHeader>
            <CardContent className="text-sm text-muted-foreground">Preparado para filtros, CSV y PDF.</CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
