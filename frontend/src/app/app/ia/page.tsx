import { Bot, Lock } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

export default function AiPage() {
  return (
    <div className="space-y-6">
      <div>
        <div className="flex items-center gap-2">
          <h1 className="text-2xl font-semibold">IA para RRHH</h1>
          <span className="flex items-center gap-1 rounded bg-primary/10 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-primary">
            <Lock className="h-3 w-3" /> Premium
          </span>
        </div>
        <p className="text-sm text-muted-foreground">Modulo disponible en el plan Premium.</p>
      </div>
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Bot className="h-5 w-5 text-primary" /> Asistente de Recursos Humanos
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <p className="text-sm text-muted-foreground">
            Un asistente conversacional para consultas frecuentes: quien llego tarde esta semana,
            contratos que vencen este mes, documentos vencidos, resumen de ausentismo y generacion
            de solicitudes.
          </p>
          <div className="rounded-lg border border-dashed border-border bg-muted p-4 text-sm text-muted-foreground">
            Contacta al equipo comercial para habilitar este modulo en tu plan.
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
