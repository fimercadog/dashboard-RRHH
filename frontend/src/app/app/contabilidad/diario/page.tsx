"use client";

import React, { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { api } from "@/lib/api";
import { JournalEntry, JournalEntryLine } from "@/lib/types";

const STATUS_LABEL: Record<string, string> = {
  draft: "Borrador", posted: "Publicado", reversed: "Revertido",
};

export default function DiarioPage() {
  const [entries, setEntries] = useState<JournalEntry[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState("");
  const [detail, setDetail] = useState<JournalEntry | null>(null);
  const [lines, setLines] = useState<JournalEntryLine[]>([]);
  const [loadingDetail, setLoadingDetail] = useState(false);
  const [reversing, setReversing] = useState(false);

  const load = () => {
    setLoading(true);
    api.get<{ data: JournalEntry[] }>("/journal-entries?per_page=100")
      .then((r) => setEntries(r.data.data))
      .catch(() => setError("Error al cargar el diario."))
      .finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, []);

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
      setDetail(null); load();
    } catch (e: any) {
      alert(e?.response?.data?.message || "Error al revertir.");
    } finally {
      setReversing(false);
    }
  };

  const filtered = entries.filter((e) =>
    !search ||
    e.number.toLowerCase().includes(search.toLowerCase()) ||
    e.description.toLowerCase().includes(search.toLowerCase())
  );

  const totalDebit = lines.reduce((s, l) => s + Number(l.debit), 0);
  const totalCredit = lines.reduce((s, l) => s + Number(l.credit), 0);

  return (
    <div className="mx-auto max-w-5xl space-y-6 p-6">
      <div>
        <h1 className="text-2xl font-bold">Libro Diario</h1>
        <p className="text-sm text-muted-foreground">Registro de asientos contables (solo lectura + reversión)</p>
      </div>

      <Input
        placeholder="Buscar por número o descripción..."
        value={search}
        onChange={(e) => setSearch(e.target.value)}
        className="max-w-sm"
      />

      {loading && <p className="text-sm text-muted-foreground">Cargando...</p>}
      {error && <p className="text-sm text-destructive">{error}</p>}

      {!loading && !error && (
        <div className="rounded-lg border">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-muted/40">
                <th className="w-10 px-3 py-2 text-left font-medium">N.º</th>
                <th className="px-4 py-2 text-left font-medium">Número</th>
                <th className="px-4 py-2 text-left font-medium">Fecha</th>
                <th className="px-4 py-2 text-left font-medium">Descripción</th>
                <th className="px-4 py-2 text-left font-medium">Referencia</th>
                <th className="px-4 py-2 text-left font-medium">Estado</th>
                <th className="px-4 py-2" />
              </tr>
            </thead>
            <tbody>
              {filtered.length === 0 && (
                <tr>
                  <td colSpan={7} className="px-4 py-8 text-center text-muted-foreground">
                    {search ? "Sin resultados." : "No hay asientos registrados."}
                  </td>
                </tr>
              )}
              {filtered.map((e, i) => (
                <tr key={e.id} className="border-b last:border-0 odd:bg-background even:bg-muted/30 hover:bg-muted/50">
                  <td className="px-3 py-2 text-xs tabular-nums text-muted-foreground">{i + 1}</td>
                  <td className="px-4 py-2 font-mono font-medium">{e.number}</td>
                  <td className="px-4 py-2">{e.date}</td>
                  <td className="px-4 py-2 max-w-xs truncate">{e.description}</td>
                  <td className="px-4 py-2 text-xs text-muted-foreground">
                    {e.reference_type ? `${e.reference_type} #${e.reference_id}` : "—"}
                  </td>
                  <td className="px-4 py-2">
                    <Badge variant={badgeVariant(e.status)}>{STATUS_LABEL[e.status]}</Badge>
                  </td>
                  <td className="px-4 py-2">
                    <Button variant="ghost" size="sm" onClick={() => openDetail(e)}>Ver</Button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

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
                          <td className="px-3 py-1.5 font-mono text-xs">
                            {l.account_code} {l.account_name}
                          </td>
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
