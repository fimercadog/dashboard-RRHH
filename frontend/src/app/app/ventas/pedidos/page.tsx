"use client";

import * as React from "react";
import { CheckCircle, Plus } from "lucide-react";
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
import { Client, Quote, SaleOrder, Warehouse } from "@/lib/types";
import { useApiTable } from "@/lib/use-api-table";

const COP = (v: number) =>
  new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(v);

const STATUS_LABELS: Record<string, string> = {
  draft: "Borrador", confirmed: "Confirmado", partial: "Parcial", fulfilled: "Entregado", cancelled: "Cancelado",
};
const STATUS_COLOR: Record<string, string> = {
  draft: "bg-muted text-muted-foreground",
  confirmed: "bg-blue-100 text-blue-800",
  partial: "bg-amber-100 text-amber-800",
  fulfilled: "bg-green-100 text-green-800",
  cancelled: "bg-red-100 text-red-800",
};

type ApiErrors = Record<string, string[]>;

// ── Create Sale Order Modal ───────────────────────────────────────────────────

type CreateSaleOrderModalProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSaved: () => void;
};

function CreateSaleOrderModal({ open, onOpenChange, onSaved }: CreateSaleOrderModalProps) {
  const today = new Date().toISOString().slice(0, 10);

  const [clientId, setClientId] = React.useState<number | "">("");
  const [warehouseId, setWarehouseId] = React.useState<number | "">("");
  const [quoteId, setQuoteId] = React.useState<number | "">("");
  const [date, setDate] = React.useState(today);
  const [notes, setNotes] = React.useState("");
  const [items, setItems] = React.useState<SaleLine[]>([newSaleLine()]);

  const [clients, setClients] = React.useState<Client[]>([]);
  const [warehouses, setWarehouses] = React.useState<Warehouse[]>([]);
  const [quotes, setQuotes] = React.useState<Quote[]>([]);
  const [products, setProducts] = React.useState<ProductOption[]>([]);
  const [loadingData, setLoadingData] = React.useState(false);

  const [saving, setSaving] = React.useState(false);
  const [errors, setErrors] = React.useState<ApiErrors>({});

  React.useEffect(() => {
    if (!open) return;
    setLoadingData(true);
    Promise.all([
      api.get<PaginatedResponse<Client>>("/clients", { params: { per_page: 500 } }),
      api.get<PaginatedResponse<Warehouse>>("/warehouses", { params: { per_page: 100 } }),
      api.get<PaginatedResponse<ProductOption>>("/products", { params: { per_page: 500 } }),
      api.get<PaginatedResponse<Quote>>("/quotes", { params: { per_page: 200 } }),
    ])
      .then(([c, w, p, q]) => { setClients(c.data.data); setWarehouses(w.data.data); setProducts(p.data.data); setQuotes(q.data.data); })
      .catch(() => toast.error("No se pudieron cargar los datos de referencia."))
      .finally(() => setLoadingData(false));
  }, [open]);

  function onQuoteChange(qId: number | "") {
    setQuoteId(qId);
    if (qId) {
      const q = quotes.find((x) => x.id === qId);
      if (q?.client) setClientId((q.client as any).id ?? "");
    }
  }

  function resetForm() {
    setClientId(""); setWarehouseId(""); setQuoteId(""); setDate(today); setNotes(""); setItems([newSaleLine()]); setErrors({});
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
    if (!warehouseId) { setErrors({ warehouse_id: ["La bodega es requerida."] }); return; }
    if (validItems.length === 0) { setErrors({ items: ["Se requiere al menos una línea válida."] }); return; }

    setSaving(true);
    setErrors({});
    try {
      await api.post("/sale-orders", {
        client_id: clientId,
        warehouse_id: warehouseId,
        quote_id: quoteId || null,
        date,
        notes: notes || null,
        items: validItems.map((l) => ({
          product_id: l.product_id,
          quantity: Number(l.quantity),
          unit_price: Number(l.unit_price),
          discount_pct: Number(l.discount_pct || 0),
        })),
      });
      toast.success("Pedido de venta creado");
      onSaved();
      handleOpenChange(false);
    } catch (err) {
      const res = (err as { response?: { status?: number; data?: { errors?: ApiErrors; message?: string } } }).response;
      if (res?.status === 422 && res.data?.errors) {
        setErrors(res.data.errors);
        toast.error("Hay campos por corregir.");
      } else if (res?.status === 403) {
        toast.error("Sin permiso para crear pedidos.");
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
          <DialogTitle>Nuevo pedido de venta</DialogTitle>
        </DialogHeader>

        <form id={formId} onSubmit={submit} className="space-y-5">
          <div className="grid gap-4 sm:grid-cols-2">
            {/* Cotización origen */}
            <label className="space-y-1.5 text-sm sm:col-span-2">
              <span className="font-medium">Cotización origen (opcional)</span>
              <select
                value={quoteId}
                onChange={(e) => onQuoteChange(e.target.value ? Number(e.target.value) : "")}
                disabled={loadingData}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-primary disabled:opacity-50"
              >
                <option value="">Sin cotización asociada</option>
                {quotes.map((q) => <option key={q.id} value={q.id}>{q.number}</option>)}
              </select>
            </label>

            {/* Cliente */}
            <label className="space-y-1.5 text-sm">
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

            {/* Bodega */}
            <label className="space-y-1.5 text-sm">
              <span className="font-medium">Bodega <span className="text-destructive">*</span></span>
              <select
                required
                value={warehouseId}
                onChange={(e) => { setWarehouseId(e.target.value ? Number(e.target.value) : ""); setErrors((p) => ({ ...p, warehouse_id: [] })); }}
                disabled={loadingData}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-primary disabled:opacity-50"
              >
                <option value="">{loadingData ? "Cargando..." : "Seleccionar bodega"}</option>
                {warehouses.map((w) => <option key={w.id} value={w.id}>{w.name}</option>)}
              </select>
              {errors.warehouse_id?.[0] && <span className="text-xs text-destructive">{errors.warehouse_id[0]}</span>}
            </label>

            {/* Fecha */}
            <label className="space-y-1.5 text-sm">
              <span className="font-medium">Fecha <span className="text-destructive">*</span></span>
              <Input type="date" required value={date} onChange={(e) => setDate(e.target.value)} />
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
            <p className="text-sm font-medium">Productos <span className="text-destructive">*</span></p>
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

          {/* Total */}
          <div className="flex justify-end gap-8 border-t pt-3 text-sm font-semibold">
            <span className="font-normal text-muted-foreground">Subtotal</span>
            <span className="tabular-nums w-32 text-right">{COP(lineSubtotal)}</span>
          </div>
        </form>

        <DialogFooter>
          <Button type="button" variant="outline" onClick={() => handleOpenChange(false)} disabled={saving}>
            Cancelar
          </Button>
          <Button type="submit" form={formId} disabled={saving || loadingData}>
            {saving ? "Guardando..." : "Crear pedido"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

// ── Confirm action ────────────────────────────────────────────────────────────

function ConfirmOrderButton({ order, onDone }: { order: SaleOrder; onDone: () => void }) {
  const [loading, setLoading] = React.useState(false);

  if (order.status !== "draft") return null;

  async function doConfirm() {
    setLoading(true);
    try {
      await api.post(`/sale-orders/${order.id}/confirm`);
      toast.success("Pedido confirmado");
      onDone();
    } catch (err) {
      const res = (err as { response?: { data?: { message?: string } } }).response;
      toast.error(res?.data?.message ?? "Error al confirmar el pedido.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <Button
      variant="ghost"
      size="sm"
      onClick={doConfirm}
      disabled={loading}
      className="text-blue-700 hover:text-blue-900 hover:bg-blue-50"
    >
      <CheckCircle className="h-4 w-4" />
      {loading ? "Confirmando..." : "Confirmar"}
    </Button>
  );
}

// ── Page ─────────────────────────────────────────────────────────────────────

const baseColumns: AppColumnDef<SaleOrder>[] = [
  { accessorKey: "number", header: "Número" },
  { header: "Cliente", cell: ({ row }) => { const c = row.original.client as any; return c?.company_name ?? (`${c?.first_name ?? ""} ${c?.last_name ?? ""}`.trim() || "-"); } },
  { header: "Bodega", cell: ({ row }) => (row.original as any).warehouse?.name ?? "-" },
  dateColumn<SaleOrder>("date", "Fecha"),
  { header: "Total", cell: ({ row }) => COP(row.original.total) },
  {
    header: "Estado",
    cell: ({ row }) => (
      <Badge className={STATUS_COLOR[row.original.status] ?? ""}>{STATUS_LABELS[row.original.status] ?? row.original.status}</Badge>
    ),
  },
];

export default function PedidosPage() {
  const table = useApiTable<SaleOrder>("/sale-orders");
  const [modalOpen, setModalOpen] = React.useState(false);

  const columns: AppColumnDef<SaleOrder>[] = [
    ...baseColumns,
    {
      id: "actions",
      header: "",
      cell: ({ row }) => (
        <div className="flex justify-end">
          <ConfirmOrderButton order={row.original} onDone={table.refresh} />
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Pedidos de venta</h1>
          <p className="text-sm text-muted-foreground">Pedidos confirmados por clientes.</p>
        </div>
        <Button onClick={() => setModalOpen(true)}>
          <Plus className="h-4 w-4" /> Nuevo pedido
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

      <CreateSaleOrderModal open={modalOpen} onOpenChange={setModalOpen} onSaved={table.refresh} />
    </div>
  );
}
