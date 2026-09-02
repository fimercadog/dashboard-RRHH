"use client";

import * as React from "react";
import { motion, useReducedMotion, type Variants } from "framer-motion";
import {
  Activity,
  ArrowDownRight,
  ArrowUpRight,
  Building2,
  CalendarDays,
  Cake,
  ChartPie,
  Clock3,
  FileWarning,
  Filter,
  HeartPulse,
  Minus,
  Plane,
  RefreshCw,
  ShieldCheck,
  TrendingUp,
  UserMinus,
  UserPlus,
  UserRound,
  Users,
  type LucideIcon,
} from "lucide-react";
import {
  Area,
  AreaChart,
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  LabelList,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { useDashboard, type DashboardData, type Delta } from "@/lib/use-dashboard";
import { useCountUp } from "@/lib/use-count-up";

const TONE = {
  green: "#0e8f5c",
  amber: "#b9770e",
  red: "#c23b2b",
  indigo: "#4f46e5",
  slate: "#64748b",
  violet: "#7c3aed",
  sky: "#0284c7",
  wine: "#a3175a",
};
const CATEGORICAL = [TONE.indigo, TONE.sky, TONE.green, TONE.amber, TONE.violet, TONE.wine, TONE.slate];
const STATUS_META: Record<string, { label: string; color: string }> = {
  active: { label: "Activos", color: TONE.green },
  on_leave: { label: "En licencia", color: TONE.amber },
  terminated: { label: "Retirados", color: TONE.slate },
};

function monthShort(ym: string) {
  return new Date(`${ym}-01T00:00:00`).toLocaleDateString("es-CO", { month: "short" }).replace(".", "");
}
function fmt(n: number) {
  return Math.round(n).toLocaleString("es-CO");
}
function timeAgo(from: Date | undefined, nowMs: number) {
  if (!from) return "";
  const s = Math.max(0, Math.round((nowMs - from.getTime()) / 1000));
  if (s < 60) return `hace ${s}s`;
  if (s < 3600) return `hace ${Math.round(s / 60)} min`;
  return `hace ${Math.round(s / 3600)} h`;
}

const container: Variants = { hidden: {}, show: { transition: { staggerChildren: 0.05 } } };
const item: Variants = {
  hidden: { opacity: 0, y: 12 },
  show: { opacity: 1, y: 0, transition: { duration: 0.4, ease: "easeOut" } },
};

function IconBadge({ tone, size = 10, children }: { tone: string; size?: 9 | 10 | 11; children: React.ReactNode }) {
  const cls = size === 9 ? "size-9" : size === 11 ? "size-11" : "size-10";
  return (
    <span
      className={`grid ${cls} shrink-0 place-items-center rounded-xl ring-1 ring-inset ring-current/15`}
      style={{ backgroundColor: `${tone}1f`, color: tone }}
    >
      {children}
    </span>
  );
}

function DeltaChip({ delta, unit = "%" }: { delta: Delta; unit?: string }) {
  if (delta.pct === null) return null;
  const up = delta.pct > 0;
  const flat = delta.pct === 0;
  const Icon = flat ? Minus : up ? ArrowUpRight : ArrowDownRight;
  const color = flat ? "text-muted-foreground" : up ? "text-success" : "text-destructive";
  return (
    <span className={`inline-flex items-center gap-0.5 text-xs font-medium ${color}`}>
      <Icon className="size-3.5" />
      {up ? "+" : ""}
      {delta.pct}
      {unit}
    </span>
  );
}

function AnimatedValue({ value, suffix = "" }: { value: number; suffix?: string }) {
  const v = useCountUp(value);
  return (
    <span className="tabular-nums">
      {fmt(v)}
      {suffix}
    </span>
  );
}

function KpiCard({
  label,
  value,
  suffix,
  icon: Icon,
  tone,
  delta,
  hint,
  emphasis,
}: {
  label: string;
  value: number;
  suffix?: string;
  icon: LucideIcon;
  tone: string;
  delta?: Delta;
  hint?: string;
  emphasis?: boolean;
}) {
  return (
    <Card
      className={`relative overflow-hidden border-border/70 ${
        emphasis ? "ring-1 ring-primary/25 shadow-[0_0_0_1px_rgba(99,102,241,0.06),0_8px_30px_-12px_rgba(99,102,241,0.25)]" : ""
      }`}
    >
      {emphasis ? (
        <span
          aria-hidden
          className="pointer-events-none absolute -right-10 -top-10 size-32 rounded-full opacity-20 blur-2xl"
          style={{ backgroundColor: tone }}
        />
      ) : null}
      <CardContent className="flex items-start justify-between gap-3 p-4">
        <div className="min-w-0">
          <p className="text-xs leading-tight text-muted-foreground">{label}</p>
          <p className={`mt-1.5 font-semibold ${emphasis ? "text-3xl" : "text-2xl"}`}>
            <AnimatedValue value={value} suffix={suffix} />
          </p>
          <div className="mt-1 flex items-center gap-2">
            {delta ? <DeltaChip delta={delta} /> : null}
            {hint ? <span className="truncate text-xs text-muted-foreground">{hint}</span> : null}
          </div>
        </div>
        <IconBadge tone={tone} size={emphasis ? 11 : 10}>
          <Icon className="size-5" />
        </IconBadge>
      </CardContent>
    </Card>
  );
}

function MiniStat({ label, value, icon: Icon, tone }: { label: string; value: number; icon: LucideIcon; tone: string }) {
  return (
    <Card className="border-border/60">
      <CardContent className="flex items-center gap-3 p-3.5">
        <IconBadge tone={tone} size={9}>
          <Icon className="size-4" />
        </IconBadge>
        <div className="min-w-0">
          <p className="text-[11px] leading-tight text-muted-foreground">{label}</p>
          <p className="mt-0.5 text-lg font-semibold tabular-nums">{fmt(value)}</p>
        </div>
      </CardContent>
    </Card>
  );
}

function SectionLabel({ children }: { children: React.ReactNode }) {
  return <h2 className="mb-3 text-xs font-medium uppercase tracking-wide text-muted-foreground">{children}</h2>;
}

function ChartFrame({
  title,
  description,
  icon: Icon,
  tone,
  action,
  children,
  className = "",
}: {
  title: string;
  description?: string;
  icon: LucideIcon;
  tone: string;
  action?: React.ReactNode;
  children: React.ReactNode;
  className?: string;
}) {
  return (
    <Card className={`border-border/70 ${className}`}>
      <CardContent className="p-5">
        <div className="mb-4 flex items-start justify-between gap-3">
          <div className="min-w-0">
            <h3 className="text-sm font-semibold">{title}</h3>
            {description ? <p className="mt-0.5 text-xs text-muted-foreground">{description}</p> : null}
          </div>
          <div className="flex items-center gap-2">
            {action}
            <IconBadge tone={tone} size={9}>
              <Icon className="size-4" />
            </IconBadge>
          </div>
        </div>
        {children}
      </CardContent>
    </Card>
  );
}

type TipRow = { name?: string; value?: number | string; color?: string };
function ChartTip({
  active,
  payload,
  label,
  format,
}: {
  active?: boolean;
  payload?: TipRow[];
  label?: string | number;
  format?: (v: string) => string;
}) {
  if (!active || !payload?.length) return null;
  return (
    <div className="rounded-lg border border-border bg-card/95 px-3 py-2 text-xs shadow-xl backdrop-blur">
      {label != null && <p className="mb-1 font-medium">{format ? format(String(label)) : label}</p>}
      {payload.map((row, i) => (
        <div key={i} className="flex items-center gap-2">
          <span className="size-2 rounded-full" style={{ backgroundColor: row.color }} />
          <span className="text-muted-foreground">{row.name}</span>
          <span className="ml-auto font-semibold tabular-nums">{row.value}</span>
        </div>
      ))}
    </div>
  );
}

function EmptyChart({ label }: { label: string }) {
  return <div className="flex h-full items-center justify-center text-sm text-muted-foreground">{label}</div>;
}

/* ---------------- charts ---------------- */

function AttendanceTrend({ data }: { data: DashboardData["trends"]["attendance_monthly"] }) {
  // El mes en curso es parcial: se excluye para que la linea no caiga en picado.
  const closed = data.filter((d) => !d.partial);
  const rows = (closed.length >= 2 ? closed : data).map((d) => ({ ...d, label: monthShort(d.month) }));
  if (rows.every((d) => d.present + d.late + d.absent === 0)) return <EmptyChart label="Sin datos de asistencia." />;
  return (
    <div className="h-64">
      <ResponsiveContainer width="100%" height="100%">
        <AreaChart data={rows} margin={{ top: 4, right: 12, bottom: 0, left: 0 }}>
          <defs>
            <linearGradient id="rate-fill" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stopColor={TONE.indigo} stopOpacity={0.35} />
              <stop offset="100%" stopColor={TONE.indigo} stopOpacity={0} />
            </linearGradient>
          </defs>
          <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="var(--border)" strokeOpacity={0.5} />
          <XAxis dataKey="label" tickLine={false} axisLine={false} tick={{ fontSize: 11, fill: "var(--muted-foreground)" }} />
          <YAxis
            domain={[80, 100]}
            ticks={[80, 85, 90, 95, 100]}
            width={40}
            tickLine={false}
            axisLine={false}
            tick={{ fontSize: 11, fill: "var(--muted-foreground)" }}
            tickFormatter={(v) => `${v}%`}
          />
          <Tooltip content={<ChartTip />} />
          <Area
            type="monotone"
            dataKey="rate"
            name="Asistencia"
            stroke={TONE.indigo}
            strokeWidth={2}
            fill="url(#rate-fill)"
            dot={{ r: 2.5, strokeWidth: 0, fill: TONE.indigo }}
            activeDot={{ r: 4 }}
          />
        </AreaChart>
      </ResponsiveContainer>
    </div>
  );
}

function DonutStatus({ rows }: { rows: DashboardData["headcount_by_status"] }) {
  const data = rows
    .filter((r) => r.total > 0)
    .map((r) => ({ name: STATUS_META[r.status]?.label ?? r.status, value: r.total, color: STATUS_META[r.status]?.color ?? TONE.slate }));
  const total = data.reduce((s, d) => s + d.value, 0);
  if (total === 0) return <EmptyChart label="Sin plantilla registrada." />;
  return (
    <div className="flex flex-col items-center gap-4 sm:flex-row">
      <div className="relative h-40 w-40 shrink-0">
        <ResponsiveContainer width="100%" height="100%">
          <PieChart>
            <Pie
              data={data}
              dataKey="value"
              nameKey="name"
              innerRadius="66%"
              outerRadius="100%"
              paddingAngle={3}
              strokeWidth={2}
              style={{ stroke: "var(--card)" }}
            >
              {data.map((d) => (
                <Cell key={d.name} fill={d.color} />
              ))}
            </Pie>
            <Tooltip content={<ChartTip />} />
          </PieChart>
        </ResponsiveContainer>
        <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
          <span className="text-2xl font-semibold tabular-nums">{total}</span>
          <span className="text-[10px] uppercase tracking-wide text-muted-foreground">Total</span>
        </div>
      </div>
      <ul className="w-full space-y-2.5 text-sm sm:min-w-0 sm:flex-1">
        {data.map((d) => (
          <li key={d.name} className="flex items-center gap-2">
            <span className="size-2.5 shrink-0 rounded-full" style={{ backgroundColor: d.color }} />
            <span className="min-w-0 flex-1 truncate text-muted-foreground">{d.name}</span>
            <span className="shrink-0 font-medium tabular-nums">{d.value}</span>
            <span className="w-9 shrink-0 text-right text-xs text-muted-foreground tabular-nums">
              {Math.round((d.value / total) * 100)}%
            </span>
          </li>
        ))}
      </ul>
    </div>
  );
}

function AttendanceFunnel({ stages }: { stages: DashboardData["attendance_funnel"] }) {
  const top = stages[0]?.count || 1;
  return (
    <div className="space-y-4">
      {stages.map((stage, i) => {
        const prev = i === 0 ? null : stages[i - 1].count;
        const conv = prev && prev > 0 ? Math.round((stage.count / prev) * 100) : null;
        return (
          <div key={stage.stage} className="flex items-center gap-3">
            <span className="w-24 shrink-0 text-sm text-muted-foreground sm:w-36">{stage.stage}</span>
            <div className="h-9 flex-1 overflow-hidden rounded-lg bg-muted">
              <div
                className="flex h-full items-center rounded-lg pl-3 text-xs font-semibold text-white/90 transition-[width]"
                style={{ width: `${Math.max(12, (stage.count / top) * 100)}%`, backgroundColor: CATEGORICAL[i % CATEGORICAL.length] }}
              >
                {stage.count}
              </div>
            </div>
            <span
              className="w-16 shrink-0 text-right text-xs tabular-nums"
              style={{ color: conv !== null && conv < 100 ? TONE.amber : "var(--muted-foreground)" }}
              title={conv !== null ? `${conv}% pasa de la etapa anterior` : undefined}
            >
              {conv !== null ? `${conv}%` : ""}
            </span>
          </div>
        );
      })}
      <p className="pt-1 text-xs text-muted-foreground">
        Conversion total:{" "}
        <span className="font-medium text-foreground">
          {top > 0 ? Math.round(((stages.at(-1)?.count ?? 0) / top) * 100) : 0}%
        </span>{" "}
        de la plantilla activa llega a tiempo.
      </p>
    </div>
  );
}

function HeadcountFlow({ data }: { data: DashboardData["trends"]["headcount_flow"] }) {
  const rows = data.map((d) => ({ ...d, label: monthShort(d.month) }));
  return (
    <>
      <div className="mb-3 flex gap-4 text-xs text-muted-foreground">
        <span className="flex items-center gap-1.5">
          <span className="size-2.5 rounded-full" style={{ backgroundColor: TONE.green }} /> Contrataciones
        </span>
        <span className="flex items-center gap-1.5">
          <span className="size-2.5 rounded-full" style={{ backgroundColor: TONE.slate }} /> Retiros
        </span>
      </div>
      <div className="h-52">
        <ResponsiveContainer width="100%" height="100%">
          <BarChart data={rows} margin={{ top: 4, right: 8, bottom: 0, left: 0 }} barGap={3}>
            <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="var(--border)" strokeOpacity={0.5} />
            <XAxis dataKey="label" tickLine={false} axisLine={false} tick={{ fontSize: 11, fill: "var(--muted-foreground)" }} />
            <YAxis allowDecimals={false} width={24} tickLine={false} axisLine={false} tick={{ fontSize: 11, fill: "var(--muted-foreground)" }} />
            <Tooltip cursor={{ fill: "var(--muted)", opacity: 0.4 }} content={<ChartTip />} />
            <Bar dataKey="hires" name="Contrataciones" fill={TONE.green} radius={[3, 3, 0, 0]} maxBarSize={16} />
            <Bar dataKey="terminations" name="Retiros" fill={TONE.slate} radius={[3, 3, 0, 0]} maxBarSize={16} />
          </BarChart>
        </ResponsiveContainer>
      </div>
    </>
  );
}

function DepartmentBars({ rows }: { rows: DashboardData["headcount_by_department"] }) {
  if (rows.length === 0) return <EmptyChart label="Sin departamentos." />;
  const data = rows.map((r, i) => ({ ...r, fill: CATEGORICAL[i % CATEGORICAL.length] }));
  return (
    <div style={{ height: Math.max(140, data.length * 44) }}>
      <ResponsiveContainer width="100%" height="100%">
        <BarChart data={data} layout="vertical" margin={{ top: 0, right: 28, bottom: 0, left: 8 }}>
          <CartesianGrid strokeDasharray="3 3" horizontal={false} stroke="var(--border)" strokeOpacity={0.5} />
          <XAxis type="number" allowDecimals={false} tickLine={false} axisLine={false} tick={{ fontSize: 11, fill: "var(--muted-foreground)" }} />
          <YAxis
            type="category"
            dataKey="department"
            width={104}
            tickLine={false}
            axisLine={false}
            tick={{ fontSize: 11, fill: "var(--muted-foreground)" }}
          />
          <Tooltip cursor={{ fill: "var(--muted)", opacity: 0.4 }} content={<ChartTip />} />
          <Bar dataKey="total" name="Empleados" radius={[0, 5, 5, 0]} maxBarSize={26}>
            {data.map((r) => (
              <Cell key={r.department} fill={r.fill} />
            ))}
            <LabelList dataKey="total" position="right" className="fill-foreground" fontSize={11} />
          </Bar>
        </BarChart>
      </ResponsiveContainer>
    </div>
  );
}

/* ---------------- activity + secondary ---------------- */

const ACTIVITY_META: { match: string; icon: LucideIcon; tone: string }[] = [
  { match: "login", icon: ShieldCheck, tone: TONE.slate },
  { match: "employee", icon: Users, tone: TONE.indigo },
  { match: "vacation", icon: Plane, tone: TONE.sky },
  { match: "permission", icon: CalendarDays, tone: TONE.violet },
  { match: "sick", icon: HeartPulse, tone: TONE.red },
  { match: "document", icon: FileWarning, tone: TONE.amber },
  { match: "attendance", icon: Activity, tone: TONE.green },
  { match: "user", icon: UserPlus, tone: TONE.indigo },
  { match: "role", icon: ShieldCheck, tone: TONE.wine },
];
function activityMeta(action: string) {
  return ACTIVITY_META.find((m) => action.toLowerCase().includes(m.match)) ?? { icon: Activity, tone: TONE.slate };
}
function humanize(action: string) {
  const t = action.replace(/[._]/g, " ").trim();
  return t.charAt(0).toUpperCase() + t.slice(1);
}

function ActivityTimeline({ items }: { items: DashboardData["recent_activity"] }) {
  if (!items.length) return <p className="text-sm text-muted-foreground">Sin actividad registrada.</p>;
  return (
    <ol className="relative space-y-4 before:absolute before:left-[15px] before:top-2 before:h-[calc(100%-1rem)] before:w-px before:bg-border">
      {items.map((it) => {
        const meta = activityMeta(it.action);
        const Icon = meta.icon;
        return (
          <li key={it.id} className="relative flex gap-3">
            <span
              className="z-10 grid size-8 shrink-0 place-items-center rounded-full ring-4 ring-card"
              style={{ backgroundColor: `${meta.tone}22`, color: meta.tone }}
            >
              <Icon className="size-4" />
            </span>
            <div className="min-w-0 flex-1">
              <p className="truncate text-sm font-medium">{humanize(it.action)}</p>
              <p className="text-xs text-muted-foreground">
                {it.user ?? "Sistema"}
                {it.module ? ` · ${it.module}` : ""} ·{" "}
                {new Date(it.created_at).toLocaleDateString("es-CO", { day: "2-digit", month: "short", hour: "2-digit", minute: "2-digit" })}
              </p>
            </div>
          </li>
        );
      })}
    </ol>
  );
}

function daysUntilBirthday(iso: string, nowMs: number) {
  const now = new Date(nowMs);
  const b = new Date(iso);
  const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const next = new Date(now.getFullYear(), b.getMonth(), b.getDate());
  if (next < startOfToday) next.setFullYear(now.getFullYear() + 1);
  return Math.round((next.getTime() - startOfToday.getTime()) / 86_400_000);
}

function BirthdayList({ people, nowMs }: { people: DashboardData["upcoming_events"]["birthdays"]; nowMs: number }) {
  if (!people.length) return <p className="text-sm text-muted-foreground">Sin cumpleanos proximos.</p>;
  return (
    <ul className="space-y-3">
      {people.map((p) => {
        const left = daysUntilBirthday(p.birth_date, nowMs);
        return (
          <li key={p.id} className="flex items-center gap-3">
            <span className="grid size-9 shrink-0 place-items-center rounded-full bg-muted text-xs font-semibold">
              {p.first_name[0]}
              {p.last_name[0]}
            </span>
            <div className="min-w-0">
              <p className="truncate text-sm font-medium">
                {p.first_name} {p.last_name}
              </p>
              <p className="text-xs text-muted-foreground">
                {new Date(p.birth_date).toLocaleDateString("es-CO", { day: "2-digit", month: "long" })}
              </p>
            </div>
            <Badge className="ml-auto shrink-0">{left === 0 ? "Hoy" : `${left} d`}</Badge>
          </li>
        );
      })}
    </ul>
  );
}

function DocsList({ docs, nowMs }: { docs: DashboardData["upcoming_events"]["documents"]; nowMs: number }) {
  if (!docs.length) return <p className="text-sm text-muted-foreground">Sin documentos proximos a vencer.</p>;
  return (
    <ul className="space-y-3">
      {docs.map((doc) => {
        const left = Math.ceil((new Date(doc.expiration_date).getTime() - nowMs) / 86_400_000);
        const tone = left <= 7 ? TONE.red : left <= 21 ? TONE.amber : TONE.slate;
        return (
          <li key={doc.id} className="flex items-center gap-3">
            <IconBadge tone={tone} size={9}>
              <FileWarning className="size-4" />
            </IconBadge>
            <div className="min-w-0">
              <p className="truncate text-sm font-medium">{doc.name}</p>
              <p className="text-xs text-muted-foreground">
                Vence {new Date(doc.expiration_date).toLocaleDateString("es-CO")}
              </p>
            </div>
            <Badge className="ml-auto shrink-0" style={{ backgroundColor: `${tone}1f`, color: tone }}>
              {left <= 0 ? "Vencido" : `${left} d`}
            </Badge>
          </li>
        );
      })}
    </ul>
  );
}

/* ---------------- states ---------------- */

function LoadingState() {
  return (
    <div className="space-y-6">
      <div className="h-9 w-48 animate-pulse rounded bg-muted" />
      <div className="grid gap-3 sm:grid-cols-3 lg:grid-cols-5">
        {Array.from({ length: 5 }).map((_, i) => (
          <div key={i} className="h-24 animate-pulse rounded-lg border border-border bg-card" />
        ))}
      </div>
      <div className="grid gap-4 lg:grid-cols-3">
        <div className="h-72 animate-pulse rounded-lg border border-border bg-card lg:col-span-2" />
        <div className="h-72 animate-pulse rounded-lg border border-border bg-card" />
      </div>
    </div>
  );
}

/* ---------------- page ---------------- */

const PERIODS = [3, 6, 12] as const;

export default function DashboardPage() {
  const { data, loading, error, fetchedAt, refresh } = useDashboard();
  const [months, setMonths] = React.useState<(typeof PERIODS)[number]>(6);
  const [nowMs, setNowMs] = React.useState(() => Date.now());
  const reduce = useReducedMotion();

  // Mantiene "hace Xs" y los conteos de dias al dia sin leer el reloj en render.
  React.useEffect(() => {
    const t = setInterval(() => setNowMs(Date.now()), 30_000);
    return () => clearInterval(t);
  }, []);

  if (loading && !data) return <LoadingState />;
  if (error && !data) {
    return (
      <div className="flex min-h-[50vh] flex-col items-center justify-center gap-3 text-center">
        <p className="text-sm text-muted-foreground">{error}</p>
        <button onClick={refresh} className="inline-flex h-9 items-center gap-2 rounded-md border border-border px-4 text-sm font-medium hover:bg-muted">
          <RefreshCw className="size-4" /> Reintentar
        </button>
      </div>
    );
  }
  if (!data) return <LoadingState />;

  const m = data.metrics;
  // +1: la tendencia excluye el mes en curso (parcial), asi quedan `months` cerrados.
  const attendanceTrend = data.trends.attendance_monthly.slice(-(months + 1));
  const headcountFlow = data.trends.headcount_flow.slice(-months);
  const onLeave = data.headcount_by_status.find((s) => s.status === "on_leave")?.total ?? 0;

  return (
    <motion.div
      variants={reduce ? undefined : container}
      initial={reduce ? undefined : "hidden"}
      animate={reduce ? undefined : "show"}
      className="space-y-7"
    >
      {/* Header */}
      <motion.div variants={item} className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">Dashboard</h1>
          <p className="text-sm text-muted-foreground">Vista general de Recursos Humanos.</p>
        </div>
        <div className="flex items-center gap-2">
          <select
            value={months}
            onChange={(e) => setMonths(Number(e.target.value) as (typeof PERIODS)[number])}
            className="h-9 rounded-md border border-border bg-card px-3 text-sm outline-none focus-visible:border-primary"
            aria-label="Periodo"
          >
            {PERIODS.map((p) => (
              <option key={p} value={p}>
                Ultimos {p} meses
              </option>
            ))}
          </select>
          <button
            onClick={refresh}
            className="inline-flex h-9 items-center gap-2 rounded-md border border-border bg-card px-3 text-sm font-medium hover:bg-muted"
          >
            <RefreshCw className={`size-4 ${loading ? "animate-spin" : ""}`} /> Actualizar
          </button>
          <span className="hidden text-xs text-muted-foreground sm:inline">Actualizado {timeAgo(fetchedAt, nowMs)}</span>
        </div>
      </motion.div>

      {/* KPI hero */}
      <motion.div variants={item}>
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
          <KpiCard
            label="Tasa de asistencia · 30 dias"
            value={data.deltas.attendance_rate.current}
            suffix="%"
            icon={TrendingUp}
            tone={TONE.indigo}
            delta={data.deltas.attendance_rate}
            emphasis
          />
          <KpiCard label="Empleados activos" value={m.active_employees ?? 0} icon={Users} tone={TONE.green} hint={`${m.total_employees ?? 0} en total`} />
          <KpiCard label="Presentes hoy" value={m.present_today ?? 0} icon={Activity} tone={TONE.green} hint={`${m.late_today ?? 0} tarde`} />
          <KpiCard label="Solicitudes pendientes" value={m.pending_requests ?? 0} icon={CalendarDays} tone={TONE.wine} delta={data.deltas.requests} />
          <KpiCard label="Contrataciones (30d)" value={data.deltas.hires.current} icon={UserPlus} tone={TONE.sky} delta={data.deltas.hires} />
        </div>
      </motion.div>

      {/* Second metrics */}
      <motion.div variants={item}>
        <SectionLabel>Detalle operativo</SectionLabel>
        <div className="grid gap-3 grid-cols-2 sm:grid-cols-3 lg:grid-cols-6">
          <MiniStat label="Ausentes hoy" value={m.absent_today ?? 0} icon={UserRound} tone={TONE.red} />
          <MiniStat label="Llegadas tarde" value={m.late_today ?? 0} icon={Clock3} tone={TONE.amber} />
          <MiniStat label="Incapacidades activas" value={m.active_sick_leaves ?? 0} icon={HeartPulse} tone={TONE.red} />
          <MiniStat label="Vacaciones proximas" value={m.upcoming_vacations ?? 0} icon={Plane} tone={TONE.sky} />
          <MiniStat label="En licencia" value={onLeave} icon={UserMinus} tone={TONE.amber} />
          <MiniStat label="Docs. por vencer" value={data.upcoming_events.documents.length} icon={FileWarning} tone={TONE.slate} />
        </div>
      </motion.div>

      {/* Analytics */}
      <motion.div variants={item}>
        <SectionLabel>Analitica</SectionLabel>
        <div className="grid gap-4 lg:grid-cols-3">
          <ChartFrame
            className="lg:col-span-2"
            title="Rendimiento de asistencia"
            description={`Tasa mensual de asistencia a tiempo — ultimos ${months} meses.`}
            icon={TrendingUp}
            tone={TONE.indigo}
          >
            <AttendanceTrend data={attendanceTrend} />
          </ChartFrame>

          <ChartFrame title="Plantilla por estado" description="Distribucion actual de colaboradores." icon={ChartPie} tone={TONE.violet}>
            <DonutStatus rows={data.headcount_by_status} />
          </ChartFrame>
        </div>

        <div className="mt-4 grid gap-4 lg:grid-cols-2">
          <ChartFrame title="Embudo de asistencia (hoy)" description="Plantilla activa que registra entrada y llega a tiempo." icon={Filter} tone={TONE.wine}>
            <AttendanceFunnel stages={data.attendance_funnel} />
          </ChartFrame>

          <ChartFrame title="Contrataciones vs retiros" description={`Movimiento de plantilla — ultimos ${months} meses.`} icon={Users} tone={TONE.green}>
            <HeadcountFlow data={headcountFlow} />
          </ChartFrame>
        </div>

        <div className="mt-4">
          <ChartFrame title="Plantilla por area" description="Colaboradores por departamento." icon={Building2} tone={TONE.indigo}>
            <DepartmentBars rows={data.headcount_by_department} />
          </ChartFrame>
        </div>
      </motion.div>

      {/* Activity */}
      <motion.div variants={item}>
        <SectionLabel>Actividad</SectionLabel>
        <Card className="border-border/70">
          <CardContent className="p-5">
            <h3 className="mb-4 text-sm font-semibold">Actividad reciente</h3>
            <ActivityTimeline items={data.recent_activity} />
          </CardContent>
        </Card>
      </motion.div>

      {/* Secondary */}
      <motion.div variants={item}>
        <div className="grid gap-4 lg:grid-cols-2">
          <Card className="border-border/70">
            <CardContent className="p-5">
              <div className="mb-4 flex items-center justify-between">
                <h3 className="text-sm font-semibold">Proximos cumpleanos</h3>
                <IconBadge tone={TONE.sky} size={9}>
                  <Cake className="size-4" />
                </IconBadge>
              </div>
              <BirthdayList people={data.upcoming_events.birthdays} nowMs={nowMs} />
            </CardContent>
          </Card>
          <Card className="border-border/70">
            <CardContent className="p-5">
              <div className="mb-4 flex items-center justify-between">
                <h3 className="text-sm font-semibold">Documentos por vencer</h3>
                <IconBadge tone={TONE.amber} size={9}>
                  <FileWarning className="size-4" />
                </IconBadge>
              </div>
              <DocsList docs={data.upcoming_events.documents} nowMs={nowMs} />
            </CardContent>
          </Card>
        </div>
      </motion.div>
    </motion.div>
  );
}
