import { Badge } from "@/components/ui/badge";
import { AIChatPreview } from "./ai-chat-preview";

export function EmployeeProfileMockup() {
  return (
    <div className="rounded-4xl border border-border bg-white p-6 shadow-(--marketing-shadow)">
      <div className="flex items-center gap-4">
        <div className="grid h-16 w-16 place-items-center rounded-2xl bg-accent text-xl font-bold text-primary">LG</div>
        <div>
          <h3 className="text-xl font-semibold text-navy">Laura Gomez</h3>
          <p className="text-sm text-muted-foreground">Diseñadora UX · Producto</p>
        </div>
        <Badge className="ml-auto bg-success/10 text-success">Activa</Badge>
      </div>
      <div className="mt-6 flex flex-wrap gap-2">
        {["Informacion", "Datos laborales", "Documentos", "Asistencia", "Vacaciones", "Novedades"].map((tab) => <span key={tab} className="rounded-full bg-muted px-3 py-1 text-xs text-navy">{tab}</span>)}
      </div>
      <div className="mt-6 grid gap-3 sm:grid-cols-2">
        {["Ingreso: 12 ene 2024", "Jefe: Camila Rojas", "Contrato: Indefinido", "Docs: 9 archivos"].map((item) => <div key={item} className="rounded-2xl border border-border p-4 text-sm text-muted-foreground">{item}</div>)}
      </div>
    </div>
  );
}

export function AttendanceMockup() {
  return (
    <div className="rounded-4xl border border-border bg-white p-6 shadow-(--marketing-shadow)">
      <div className="grid grid-cols-4 gap-3">
        {["38 presentes", "2 ausentes", "3 tarde", "1 incapacidad"].map((item) => <div key={item} className="rounded-2xl bg-muted p-3 text-center text-xs font-semibold text-navy">{item}</div>)}
      </div>
      <div className="mt-5 overflow-hidden rounded-2xl border border-border">
        {["Laura | 08:00 | 17:00 | Presente", "Carlos | 08:19 | 17:02 | Tarde", "Diana | -- | -- | Ausente"].map((row) => <div key={row} className="border-b border-border px-4 py-3 text-sm last:border-0">{row}</div>)}
      </div>
    </div>
  );
}

export function VacationFlowMockup() {
  return (
    <div className="rounded-4xl border border-border bg-white p-6 shadow-(--marketing-shadow)">
      {["Empleado solicita", "Jefe revisa", "Aprueba o rechaza", "RRHH queda informado", "Sistema actualiza saldo"].map((step, index) => (
        <div key={step} className="flex items-center gap-4 pb-4 last:pb-0">
          <span className="grid h-9 w-9 place-items-center rounded-full bg-primary text-sm font-bold text-white">{index + 1}</span>
          <p className="font-medium text-navy">{step}</p>
        </div>
      ))}
    </div>
  );
}

export function DocumentsMockup() {
  return (
    <div className="rounded-4xl border border-border bg-white p-6 shadow-(--marketing-shadow)">
      {["Contratos", "Certificados", "Anexos", "Documentos personales", "Politicas"].map((item) => <div key={item} className="mb-3 rounded-2xl bg-muted px-4 py-3 text-sm text-navy">{item}</div>)}
      <div className="rounded-2xl bg-warning/10 px-4 py-3 text-sm font-medium text-navy">El contrato de Juan Perez vence en 17 dias.</div>
    </div>
  );
}

export function ShiftsMockup() {
  return (
    <div className="rounded-4xl border border-border bg-white p-6 shadow-(--marketing-shadow)">
      {["Laura | 8-5 | 8-5 | 8-5 | Libre | 8-5", "Carlos | 6-2 | 6-2 | Libre | 6-2 | 6-2", "Diana | 2-10 | Libre | 2-10 | 2-10 | 2-10"].map((row) => <div key={row} className="mb-3 rounded-2xl border border-border px-4 py-3 text-sm text-muted-foreground">{row}</div>)}
    </div>
  );
}

export function ReportsMockup() {
  return (
    <div className="rounded-4xl border border-border bg-white p-6 shadow-(--marketing-shadow)">
      <div className="flex h-52 items-end gap-3">
        {[40, 70, 55, 85, 63, 90].map((height, index) => <div key={index} className="flex-1 rounded-t-2xl bg-primary" style={{ height: `${height}%` }} />)}
      </div>
      <p className="mt-4 text-sm text-muted-foreground">Ausentismo, tardanzas, documentos vencidos y distribucion por area.</p>
    </div>
  );
}

export function AIMockup() {
  return <AIChatPreview />;
}
