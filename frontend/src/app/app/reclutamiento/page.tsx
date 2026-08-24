import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

const stages = ["Aplicaron", "Preseleccion", "Entrevista", "Oferta", "Contratado"];

export default function AppRecruitingPage() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold">Reclutamiento</h1>
        <p className="text-sm text-muted-foreground">Vista Kanban demo preparada para conectar vacantes, candidatos y entrevistas.</p>
      </div>
      <div className="grid gap-4 lg:grid-cols-5">
        {stages.map((stage, index) => (
          <Card key={stage}>
            <CardHeader><CardTitle>{stage}</CardTitle></CardHeader>
            <CardContent className="space-y-3">
              {[0, 1].map((item) => (
                <div key={item} className="rounded-md border border-border p-3 text-sm">
                  <p className="font-medium">{index === 0 ? "Desarrollador Backend" : "Analista RRHH"}</p>
                  <p className="text-xs text-muted-foreground">{24 - index * 3 - item} candidatos</p>
                </div>
              ))}
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
}
