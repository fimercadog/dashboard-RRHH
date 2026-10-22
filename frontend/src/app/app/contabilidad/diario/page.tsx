"use client";

import * as React from "react";
import { Button } from "@/components/ui/button";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { DataTable } from "@/components/data-table/data-table";
import { api, API_BASE } from "@/lib/api";
import { AppColumnDef, dateColumn } from "@/lib/table-types";
import { JournalEntry, JournalEntryLine } from "@/lib/types";
import { useApiTable } from "@/lib/use-api-table";
import { KpiCard } from "@/components/kpi-card";
import { BookOpen, Calendar, FileEdit } from "lucide-react";

const STATUS_LABEL: Record<string, string> = {
  draft: "Borrador", posted: "Publicado", reversed: "Revertido",
};

type JournalStats = { total: number; this_month: number; draft: number };

export default function DiarioPage() {
  const table = useApiTable<JournalEntry>("/journal-entries");
  const [detail, setDetail] = React.useState<JournalEntry | null>(null);
  const [lines, setLines] = React.useState<JournalEntryLine[]>([]);
  const [loadingDetail, setLoadingDetail] = React.useState(false);
  const [reversing, setReversing] = React.useState(false);
  const [stats, setStats] = React.useState<JournalStats | null>(null);
  const [statsStatus, setStatsStatus] = React.useState<"loading" | "error" | "ready">("loading");

  React.useEffect(() => {
    api.get<JournalStats>("/journal-entries/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const openDetail = async (e: JournalEntry) => {
    setDetail(e); setLines([]); setLoadingDetail(true);
    try {
      const r = await api.get<{ data: JournalEntry }>(`/journal-entries/${e.id}`);
      setLines(r.data.data.lines ?? []);
    } catch {
      // lines stay empty
    } finally {
      setLoadingDetail(false);
    }
  };

  const reverse = async () => {
    if (!detail) return;
    if (!confirm(`¿Revertir el asiento ${detail.number}? Se creará un asiento compensatorio.`)) return;
    setReversing(true);
    try {
      await api.post(`/journal-entries/${detail.id}/reverse`, {});
      setDetail(null); table.refresh();
    } catch (e: unknown) {
      alert((e as { response?: { data?: { message?: string } } })?.response?.data?.message ?? "Error al revertir.");
    } finally {
      setReversing(false);
    }
  };

  const totalDebit = lines.reduce((s, l) => s + Number(l.debit), 0);
  const totalCredit = lines.reduce((s, l) => s + Number(l.credit), 0);

  const columns: AppColumnDef<JournalEntry>[] = [
    { accessorKey: "number", header: "Número", cell: ({ row }) => <span className="font-mono font-medium">{row.original.number}</span> },
    dateColumn<JournalEntry>("date", "Fecha"),
    { header: "Descripción", cell: ({ row }) => <span className="max-w-xs truncate block">{row.original.description}</span> },
    {
      header: "Referencia",
      cell: ({ row }) => {
        const e = row.original;
        return e.reference_type ? (
          <span className="text-xs text-muted-foreground">{e.reference_type} #{e.reference_id}</span>
        ) : "—";
      },
    },
    {
      header: "Estado",
      cell: ({ row }) => (
        <Badge variant={badgeVariant(row.original.status)}>{STATUS_LABEL[row.original.status]}</Badge>
      ),
    },
    {
      id: "actions",
      header: "",
      cell: ({ row }) => (
        <div className="flex justify-end">
          <Button variant="ghost" size="sm" onClick={() => openDetail(row.original)}>Ver</Button>
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div className="grid grid-cols-3 gap-3">
        <KpiCard label="Total asientos" value={stats?.total ?? null} icon={BookOpen} status={statsStatus} />
        <KpiCard label="Este mes" value={stats?.this_month ?? null} icon={Calendar} status={statsStatus} />
        <KpiCard label="En borrador" value={stats?.draft ?? null} icon={FileEdit} status={statsStatus} />
      </div>
      <div>
        <h1 className="text-2xl font-semibold">Libro Diario</h1>
        <p className="text-sm text-muted-foreground">Registro de asientos contables (solo lectura + reversión)</p>
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
        exportBaseUrl={`${API_BASE}/exports/journal-entries`}
        loading={table.loading}
        error={table.error}
      />

      <Dialog open={!!detail} onOpenChange={(v) => !v && setDetail(null)}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>
              Asiento {detail?.number}
              {detail && (
                <Badge className="ml-2" variant={badgeVariant(detail.status)}>{STATUS_LABEL[detail.status]}</Badge>
              )}
            </DialogTitle>
          </DialogHeader>
          {detail && (
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-2 text-sm">
                <div><span className="text-muted-foreground">Fecha:</span> {detail.date}</div>
                <div><span className="text-muted-foreground">Período:</span> {detail.accounting_period_id}</div>
                <div className="col-span-2"><span className="text-muted-foreground">Descripción:</span> {detail.description}</div>
                {detail.reference_type && (
                  <div><span className="text-muted-foreground">Referencia:</span> {detail.reference_type} #{detail.reference_id}</div>
                )}
                {detail.reversal_of_entry_id && (
                  <div className="col-span-2 text-xs text-muted-foreground">Revierte asiento #{detail.reversal_of_entry_id}</div>
                )}
                {detail.reversed_by_entry_id && (
                  <div className="col-span-2 text-xs text-muted-foreground">Revertido por asiento #{detail.reversed_by_entry_id}</div>
                )}
              </div>

              {loadingDetail ? (
                <p className="text-sm text-muted-foreground">Cargando líneas...</p>
              ) : (
                <div className="rounded-md border">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b bg-muted/40">
                        <th className="px-3 py-1.5 text-left font-medium">Cuenta</th>
                        <th className="px-3 py-1.5 text-left font-medium">Descripción</th>
                        <th className="px-3 py-1.5 text-right font-medium">Débito</th>
                        <th className="px-3 py-1.5 text-right font-medium">Crédito</th>
                      </tr>
                    </thead>
                    <tbody>
                      {lines.map((l) => (
                        <tr key={l.id} className="border-b last:border-0 odd:bg-background even:bg-muted/30">
                          <td className="px-3 py-1.5 font-mono text-xs">{l.account_code} {l.account_name}</td>
                          <td className="px-3 py-1.5 text-xs text-muted-foreground">{l.description}</td>
                          <td className="px-3 py-1.5 text-right">{Number(l.debit) > 0 ? Number(l.debit).toLocaleString("es-CO", { minimumFractionDigits: 2 }) : "—"}</td>
                          <td className="px-3 py-1.5 text-right">{Number(l.credit) > 0 ? Number(l.credit).toLocaleString("es-CO", { minimumFractionDigits: 2 }) : "—"}</td>
                        </tr>
                      ))}
                      {lines.length > 0 && (
                        <tr className="border-t bg-muted/20 font-medium">
                          <td colSpan={2} className="px-3 py-1.5 text-right text-xs">TOTALES</td>
                          <td className="px-3 py-1.5 text-right">{totalDebit.toLocaleString("es-CO", { minimumFractionDigits: 2 })}</td>
                          <td className="px-3 py-1.5 text-right">{totalCredit.toLocaleString("es-CO", { minimumFractionDigits: 2 })}</td>
                        </tr>
                      )}
                    </tbody>
                  </table>
                </div>
              )}

              <div className="flex justify-end gap-2">
                <Button variant="outline" onClick={() => setDetail(null)}>Cerrar</Button>
                {detail.status === "posted" && !detail.reversed_by_entry_id && (
                  <Button variant="destructive" onClick={reverse} disabled={reversing}>
                    {reversing ? "Revirtiendo..." : "Revertir asiento"}
                  </Button>
                )}
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
