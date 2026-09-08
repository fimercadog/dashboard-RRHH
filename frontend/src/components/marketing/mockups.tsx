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
  const stats = [
    ["38", "presentes"],
    ["2", "ausentes"],
    ["3", "tarde"],
    ["1", "incapacidad"],
  ];
  const rows: Array<{ name: string; initials: string; in: string; out: string; status: string; tone: string }> = [
    { name: "Laura Gomez", initials: "LG", in: "08:00", out: "17:00", status: "Presente", tone: "bg-success/10 text-success" },
    { name: "Carlos Ruiz", initials: "CR", in: "08:19", out: "17:02", status: "Tarde", tone: "bg-warning/10 text-warning" },
    { name: "Diana Peña", initials: "DP", in: "—", out: "—", status: "Ausente", tone: "bg-destructive/10 text-destructive" },
  ];
  return (
    <div className="rounded-4xl border border-border bg-white p-6 shadow-(--marketing-shadow)">
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        {stats.map(([value, label]) => (
          <div key={label} className="rounded-2xl bg-muted p-3 text-center">
            <p className="text-lg font-bold text-navy">{value}</p>
            <p className="text-xs text-muted-foreground">{label}</p>
          </div>
        ))}
      </div>
      <div className="mt-5 overflow-hidden rounded-2xl border border-border">
        {rows.map((row) => (
          <div key={row.name} className="flex items-center gap-3 border-b border-border px-4 py-3 last:border-0">
            <span className="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-accent text-xs font-bold text-primary">{row.initials}</span>
            <div className="min-w-0 flex-1">
              <p className="truncate text-sm font-medium text-navy">{row.name}</p>
              <p className="text-xs text-muted-foreground">{row.in} – {row.out}</p>
            </div>
            <span className={`shrink-0 rounded-md px-2 py-1 text-xs font-medium ${row.tone}`}>{row.status}</span>
          </div>
        ))}
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
  const days = ["Lun", "Mar", "Mie", "Jue", "Vie"];
  const rows: Array<{ name: string; shifts: string[] }> = [
    { name: "Laura", shifts: ["8-5", "8-5", "8-5", "Libre", "8-5"] },
    { name: "Carlos", shifts: ["6-2", "6-2", "Libre", "6-2", "6-2"] },
    { name: "Diana", shifts: ["2-10", "Libre", "2-10", "2-10", "2-10"] },
  ];
  return (
    <div className="rounded-4xl border border-border bg-white p-6 shadow-(--marketing-shadow)">
      <div className="grid grid-cols-[auto_repeat(5,1fr)] gap-x-2 gap-y-2 text-center text-xs">
        <span />
        {days.map((day) => <span key={day} className="font-semibold text-navy">{day}</span>)}
        {rows.map((row) => (
          <div key={row.name} className="contents">
            <span className="flex items-center pr-1 text-sm font-medium text-navy">{row.name}</span>
            {row.shifts.map((shift, index) => (
              <span
                key={index}
                className={`rounded-lg px-1.5 py-1.5 font-medium ${shift === "Libre" ? "bg-muted text-muted-foreground" : "bg-primary/10 text-primary"}`}
              >
                {shift}
              </span>
            ))}
          </div>
        ))}
      </div>
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
