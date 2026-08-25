import { Bot, MessageCircle } from "lucide-react";

const messages = [
  ["Empleado", "¿Cuantos dias de vacaciones tengo disponibles?"],
  ["IA", "Tienes 8 dias disponibles."],
  ["Empleado", "¿Puedo solicitar vacaciones para la proxima semana?"],
  ["IA", "Claro. Voy a crear la solicitud para aprobacion de tu jefe."],
];

export function AIChatPreview() {
  return (
    <div className="rounded-4xl border border-border bg-white p-5 shadow-(--marketing-shadow)">
      <div className="mb-5 flex items-center gap-3 border-b border-border pb-4">
        <div className="grid h-11 w-11 place-items-center rounded-full bg-success/10 text-success"><MessageCircle className="h-5 w-5" /></div>
        <div>
          <p className="font-semibold text-navy">Asistente RRHH</p>
          <p className="text-xs text-muted-foreground">Disponible 24/7</p>
        </div>
      </div>
      <div className="space-y-3">
        {messages.map(([sender, text], index) => (
          <div key={`${sender}-${index}`} className={sender === "IA" ? "flex justify-start" : "flex justify-end"}>
            <div className={sender === "IA" ? "max-w-[82%] rounded-2xl bg-muted px-4 py-3 text-sm text-navy" : "max-w-[82%] rounded-2xl bg-primary px-4 py-3 text-sm text-white"}>
              <p className="mb-1 flex items-center gap-1 text-[11px] font-semibold opacity-70">{sender === "IA" ? <Bot className="h-3 w-3" /> : null}{sender}</p>
              {text}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
