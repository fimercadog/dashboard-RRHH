"use client";

import * as React from "react";
import { Plus } from "lucide-react";
import { toast } from "sonner";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { DataTable } from "@/components/data-table/data-table";
import {
  newSaleLine,
  ProductOption,
  SaleLine,
  SaleLineItemsEditor,
  saleLineSubtotal,
} from "@/components/documents/line-items-editor";
import { api, PaginatedResponse } from "@/lib/api";
import { AppColumnDef, dateColumn } from "@/lib/table-types";
import { Client, Quote } from "@/lib/types";
import { useApiTable } from "@/lib/use-api-table";

const COP = (v: number) =>
  new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(v);

const STATUS_LABELS: Record<string, string> = {
  draft: "Borrador", sent: "Enviada", accepted: "Aceptada", expired: "Vencida", cancelled: "Cancelada",
};
const STATUS_COLOR: Record<string, string> = {
  draft: "bg-muted text-muted-foreground",
  sent: "bg-blue-100 text-blue-800",
  accepted: "bg-green-100 text-green-800",
  expired: "bg-amber-100 text-amber-800",
  cancelled: "bg-red-100 text-red-800",
};

type ApiErrors = Record<string, string[]>;

// ── Create Quote Modal ────────────────────────────────────────────────────────

type CreateQuoteModalProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSaved: () => void;
};

function CreateQuoteModal({ open, onOpenChange, onSaved }: CreateQuoteModalProps) {
  const today = new Date().toISOString().slice(0, 10);
  const in30 = new Date(Date.now() + 30 * 86400000).toISOString().slice(0, 10);

  const [clientId, setClientId] = React.useState<number | "">("");
  const [date, setDate] = React.useState(today);
  const [validUntil, setValidUntil] = React.useState(in30);
  const [notes, setNotes] = React.useState("");
  const [items, setItems] = React.useState<SaleLine[]>([newSaleLine()]);

  const [clients, setClients] = React.useState<Client[]>([]);
  const [products, setProducts] = React.useState<ProductOption[]>([]);
  const [loadingData, setLoadingData] = React.useState(false);

  const [saving, setSaving] = React.useState(false);
  const [errors, setErrors] = React.useState<ApiErrors>({});

  React.useEffect(() => {
    if (!open) return;
    setLoadingData(true);
    Promise.all([
      api.get<PaginatedResponse<Client>>("/clients", { params: { per_page: 500 } }),
      api.get<PaginatedResponse<ProductOption>>("/products", { params: { per_page: 500 } }),
    ])
      .then(([c, p]) => { setClients(c.data.data); setProducts(p.data.data); })
      .catch(() => toast.error("No se pudieron cargar los datos de referencia."))
      .finally(() => setLoadingData(false));
  }, [open]);

  function resetForm() {
    setClientId(""); setDate(today); setValidUntil(in30); setNotes(""); setItems([newSaleLine()]); setErrors({});
  }

  function handleOpenChange(next: boolean) {
    if (!next) resetForm();
    onOpenChange(next);
  }

  const lineSubtotal = items.reduce((s, l) => s + saleLineSubtotal(l), 0);
  const validItems = items.filter((l) => l.product_id !== "" && Number(l.quantity) > 0);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    if (!clientId) { setErrors({ client_id: ["El cliente es requerido."] }); return; }
    if (validItems.length === 0) { setErrors({ items: ["Se requiere al menos una línea válida."] }); return; }

    setSaving(true);
    setErrors({});
    try {
      await api.post("/quotes", {
        client_id: clientId,
        date,
        valid_until: validUntil,
        notes: notes || null,
        items: validItems.map((l) => ({
          product_id: l.product_id,
          quantity: Number(l.quantity),
          unit_price: Number(l.unit_price),
          discount_pct: Number(l.discount_pct || 0),
        })),
      });
      toast.success("Cotización creada");
      onSaved();
      handleOpenChange(false);
    } catch (err) {
      const res = (err as { response?: { status?: number; data?: { errors?: ApiErrors; message?: string } } }).response;
      if (res?.status === 422 && res.data?.errors) {
        setErrors(res.data.errors);
        toast.error("Hay campos por corregir.");
      } else if (res?.status === 403) {
        toast.error("Sin permiso para crear cotizaciones.");
      } else {
        toast.error(res?.data?.message ?? "Error al guardar.");
      }
    } finally {
      setSaving(false);
    }
  }

  const formId = React.useId();

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Nueva cotización</DialogTitle>
        </DialogHeader>

        <form id={formId} onSubmit={submit} className="space-y-5">
          <div className="grid gap-4 sm:grid-cols-2">
            {/* Cliente */}
            <label className="space-y-1.5 text-sm sm:col-span-2">
              <span className="font-medium">Cliente <span className="text-destructive">*</span></span>
              <select
                required
                value={clientId}
                onChange={(e) => { setClientId(e.target.value ? Number(e.target.value) : ""); setErrors((p) => ({ ...p, client_id: [] })); }}
                disabled={loadingData}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-primary disabled:opacity-50"
              >
                <option value="">{loadingData ? "Cargando..." : "Seleccionar cliente"}</option>
                {clients.map((c) => (
                  <option key={c.id} value={c.id}>{c.company_name ?? `${c.first_name} ${c.last_name}`}</option>
                ))}
              </select>
              {errors.client_id?.[0] && <span className="text-xs text-destructive">{errors.client_id[0]}</span>}
            </label>

            {/* Fecha */}
            <label className="space-y-1.5 text-sm">
              <span className="font-medium">Fecha <span className="text-destructive">*</span></span>
              <Input type="date" required value={date} onChange={(e) => setDate(e.target.value)} />
            </label>

            {/* Válida hasta */}
            <label className="space-y-1.5 text-sm">
              <span className="font-medium">Válida hasta <span className="text-destructive">*</span></span>
              <Input type="date" required value={validUntil} onChange={(e) => setValidUntil(e.target.value)} />
            </label>

            {/* Notas */}
            <label className="space-y-1.5 text-sm sm:col-span-2">
              <span className="font-medium">Notas</span>
              <textarea
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                rows={2}
                className="min-h-16 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none placeholder:text-muted-foreground focus-visible:border-primary"
              />
            </label>
          </div>

          {/* Line items */}
          <div className="space-y-2">
            <p className="text-sm font-medium">Productos / servicios <span className="text-destructive">*</span></p>
            {errors.items?.[0] && <span className="text-xs text-destructive">{errors.items[0]}</span>}
            <SaleLineItemsEditor
              items={items}
              products={products}
              onChange={setItems}
              errors={Object.fromEntries(
                Object.entries(errors)
                  .filter(([k]) => k.startsWith("items."))
                  .map(([k, v]) => [k, v[0]])
              )}
            />
          </div>

          {/* Totals */}
          <div className="flex flex-col items-end gap-1 border-t pt-3 text-sm font-semibold">
            <div className="flex gap-8">
              <span className="font-normal text-muted-foreground">Subtotal</span>
              <span className="tabular-nums w-32 text-right">{COP(lineSubtotal)}</span>
            </div>
          </div>
        </form>

        <DialogFooter>
          <Button type="button" variant="outline" onClick={() => handleOpenChange(false)} disabled={saving}>
            Cancelar
          </Button>
          <Button type="submit" form={formId} disabled={saving || loadingData}>
            {saving ? "Guardando..." : "Crear cotización"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

// ── Page ─────────────────────────────────────────────────────────────────────

const columns: AppColumnDef<Quote>[] = [
  { accessorKey: "number", header: "Número" },
  { header: "Cliente", cell: ({ row }) => { const c = row.original.client as any; return c?.company_name ?? (`${c?.first_name ?? ""} ${c?.last_name ?? ""}`.trim() || "-"); } },
  dateColumn<Quote>("date", "Fecha"),
  dateColumn<Quote>("valid_until", "Válida hasta"),
  { header: "Total", cell: ({ row }) => COP(row.original.total) },
  {
    header: "Estado",
    cell: ({ row }) => (
      <Badge className={STATUS_COLOR[row.original.status] ?? ""}>{STATUS_LABELS[row.original.status] ?? row.original.status}</Badge>
    ),
  },
];

export default function CotizacionesPage() {
  const table = useApiTable<Quote>("/quotes");
  const [modalOpen, setModalOpen] = React.useState(false);

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Cotizaciones</h1>
          <p className="text-sm text-muted-foreground">Cotizaciones enviadas a clientes.</p>
        </div>
        <Button onClick={() => setModalOpen(true)}>
          <Plus className="h-4 w-4" /> Nueva cotización
        </Button>
      </div>

      <DataTable
        columns={columns}
        data={table.data}
        search={table.search}
        onSearchChange={table.setSearch}
        page={table.page}
        onPageChange={table.setPage}
        loading={table.loading}
        error={table.error}
      />

      <CreateQuoteModal open={modalOpen} onOpenChange={setModalOpen} onSaved={table.refresh} />
    </div>
  );
}
