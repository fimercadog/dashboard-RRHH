import { Bot } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";

export default function AiPage() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-semibold">IA para RRHH</h1>
        <p className="text-sm text-muted-foreground">Interfaz preparada. No hay proveedor conectado todavia.</p>
      </div>
      <Card>
        <CardHeader><CardTitle className="flex items-center gap-2"><Bot className="h-5 w-5 text-primary" /> Asistente desacoplado</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          <div className="rounded-lg border border-border bg-muted p-4 text-sm text-muted-foreground">
            Preguntas futuras: quien llego tarde esta semana, contratos que vencen este mes, documentos vencidos y resumen de ausentismo.
          </div>
          <Input disabled placeholder="Configura un proveedor de IA para habilitar el asistente" />
        </CardContent>
      </Card>
    </div>
  );
}
