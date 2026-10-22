"use client";

import * as React from "react";
import { Archive, BookOpen, Calendar } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { DataTable } from "@/components/data-table/data-table";
import { KpiCard } from "@/components/kpi-card";
import { api, API_BASE } from "@/lib/api";
import { AppColumnDef, dateColumn } from "@/lib/table-types";
import { AccountingPeriod } from "@/lib/types";
import { useApiTable } from "@/lib/use-api-table";

const BLANK = { name: "", start_date: "", end_date: "" };

type AccountingStats = { total: number; open: number; closed: number };

export default function PeriodosPage() {
  const table = useApiTable<AccountingPeriod>("/accounting-periods");
  const [open, setOpen] = React.useState(false);
  const [stats, setStats] = React.useState<AccountingStats | null>(null);
  const [statsStatus, setStatsStatus] = React.useState<"loading" | "error" | "ready">("loading");

  React.useEffect(() => {
    api.get<AccountingStats>("/accounting-periods/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);
  const [form, setForm] = React.useState(BLANK);
  const [saving, setSaving] = React.useState(false);
  const [formError, setFormError] = React.useState<string | null>(null);
  const [closing, setClosing] = React.useState<number | null>(null);

  const save = async () => {
    setSaving(true); setFormError(null);
    try {
      await api.post("/accounting-periods", form);
      setOpen(false); setForm(BLANK); table.refresh();
    } catch (e: unknown) {
      setFormError((e as { response?: { data?: { message?: string } } })?.response?.data?.message ?? "Error al crear el período.");
    } finally {
      setSaving(false);
    }
  };

  const close = async (id: number) => {
    if (!confirm("¿Cerrar este período? Esta acción no se puede deshacer.")) return;
    setClosing(id);
    try {
      await api.post(`/accounting-periods/${id}/close`, {});
      table.refresh();
    } catch (e: unknown) {
      alert((e as { response?: { data?: { message?: string } } })?.response?.data?.message ?? "Error al cerrar el período.");
    } finally {
      setClosing(null);
    }
  };

  const columns: AppColumnDef<AccountingPeriod>[] = [
    { accessorKey: "name", header: "Nombre" },
    dateColumn<AccountingPeriod>("start_date", "Inicio"),
    dateColumn<AccountingPeriod>("end_date", "Fin"),
    { header: "Asientos", cell: ({ row }) => row.original.entries_count ?? "—" },
    {
      header: "Estado",
      cell: ({ row }) => (
        <Badge variant={badgeVariant(row.original.status)}>
          {row.original.status === "open" ? "Abierto" : "Cerrado"}
        </Badge>
      ),
    },
    {
      id: "actions",
      header: "",
      cell: ({ row }) => {
        const p = row.original;
        if (p.status === "open") {
          return (
            <div className="flex justify-end">
              <Button variant="outline" size="sm" onClick={() => close(p.id)} disabled={closing === p.id}>
                {closing === p.id ? "Cerrando..." : "Cerrar"}
              </Button>
            </div>
          );
        }
        if (p.status === "closed" && p.closed_at) {
          return (
            <span className="text-xs text-muted-foreground">
              Cerrado {new Date(p.closed_at).toLocaleDateString()}
            </span>
          );
        }
        return null;
      },
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Períodos Contables</h1>
          <p className="text-sm text-muted-foreground">Gestión de períodos abiertos y cerrados</p>
        </div>
        <Button onClick={() => { setFormError(null); setForm(BLANK); setOpen(true); }}>+ Nuevo período</Button>
      </div>

      <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
        <KpiCard label="Total períodos" value={stats?.total ?? null} icon={Calendar} status={statsStatus} />
        <KpiCard label="Abiertos" value={stats?.open ?? null} icon={BookOpen} status={statsStatus} />
        <KpiCard label="Cerrados" value={stats?.closed ?? null} icon={Archive} status={statsStatus} />
      </div>

      <DataTable
        columns={columns}
        data={table.data}
        search={table.search}
        onSearchChange={table.setSearch}
        page={table.page}
        onPageChange={table.setPage}
        perPage={table.perPage}
        onPerPageChange={table.setPerPage}
        exportBaseUrl={`${API_BASE}/exports/accounting-periods`}
        loading={table.loading}
        error={table.error}
      />

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle>Nuevo período contable</DialogTitle>
          </DialogHeader>
          <div className="space-y-3">
            {formError && <p className="text-sm text-destructive">{formError}</p>}
            <div>
              <label className="text-xs font-medium">Nombre *</label>
              <Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder="ej. Enero 2026" />
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="text-xs font-medium">Fecha inicio *</label>
                <Input type="date" value={form.start_date} onChange={(e) => setForm({ ...form, start_date: e.target.value })} />
              </div>
              <div>
                <label className="text-xs font-medium">Fecha fin *</label>
                <Input type="date" value={form.end_date} onChange={(e) => setForm({ ...form, end_date: e.target.value })} />
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setOpen(false)}>Cancelar</Button>
            <Button onClick={save} disabled={saving}>{saving ? "Creando..." : "Crear período"}</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
