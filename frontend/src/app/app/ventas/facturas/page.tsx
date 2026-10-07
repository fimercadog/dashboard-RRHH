"use client";

import * as React from "react";
import { CheckCircle, Plus } from "lucide-react";
import { toast } from "sonner";
import { Badge, badgeVariant } from "@/components/ui/badge";
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
import { Client, SaleInvoice, SaleOrder } from "@/lib/types";
import { useApiTable } from "@/lib/use-api-table";

const COP = (v: number) =>
  new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(v);

const STATUS_LABELS: Record<string, string> = {
  draft: "Borrador", posted: "Posteada", partially_paid: "Pago parcial", paid: "Pagada", cancelled: "Cancelada",
};
const INVOICE_STATUS_VARIANT: Record<string, "muted" | "info" | "warning" | "success" | "danger"> = {
  draft: "muted", posted: "info", partially_paid: "warning", paid: "success", cancelled: "danger",
};
const TYPE_LABELS: Record<string, string> = { invoice: "Factura", return: "Nota crédito" };

type ApiErrors = Record<string, string[]>;

// ── Create Sale Invoice Modal ─────────────────────────────────────────────────

type CreateSaleInvoiceModalProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSaved: () => void;
};

function CreateSaleInvoiceModal({ open, onOpenChange, onSaved }: CreateSaleInvoiceModalProps) {
  const today = new Date().toISOString().slice(0, 10);
  const in30 = new Date(Date.now() + 30 * 86400000).toISOString().slice(0, 10);

  const [clientId, setClientId] = React.useState<number | "">("");
  const [saleOrderId, setSaleOrderId] = React.useState<number | "">("");
  const [number, setNumber] = React.useState("");
  const [date, setDate] = React.useState(today);
  const [dueDate, setDueDate] = React.useState(in30);
  const [notes, setNotes] = React.useState("");
  const [items, setItems] = React.useState<SaleLine[]>([newSaleLine()]);

  const [clients, setClients] = React.useState<Client[]>([]);
  const [saleOrders, setSaleOrders] = React.useState<SaleOrder[]>([]);
  const [products, setProducts] = React.useState<ProductOption[]>([]);
  const [loadingData, setLoadingData] = React.useState(false);

  const [saving, setSaving] = React.useState(false);
  const [errors, setErrors] = React.useState<ApiErrors>({});

  React.useEffect(() => {
    if (!open) return;
    setLoadingData(true);
    Promise.all([
      api.get<PaginatedResponse<Client>>("/clients", { params: { per_page: 500 } }),
      api.get<PaginatedResponse<SaleOrder>>("/sale-orders", { params: { per_page: 200 } }),
      api.get<PaginatedResponse<ProductOption>>("/products", { params: { per_page: 500 } }),
    ])
      .then(([c, so, p]) => { setClients(c.data.data); setSaleOrders(so.data.data); setProducts(p.data.data); })
      .catch(() => toast.error("No se pudieron cargar los datos de referencia."))
      .finally(() => setLoadingData(false));
  }, [open]);

  function onSoChange(soId: number | "") {
    setSaleOrderId(soId);
    if (soId) {
      const so = saleOrders.find((x) => x.id === soId);
      if (so?.client) setClientId((so.client as any).id ?? "");
    }
  }

  function resetForm() {
    setClientId(""); setSaleOrderId(""); setNumber(""); setDate(today); setDueDate(in30); setNotes(""); setItems([newSaleLine()]); setErrors({});
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
      await api.post("/sale-invoices", {
        client_id: clientId,
        sale_order_id: saleOrderId || null,
        number,
        date,
        due_date: dueDate,
        notes: notes || null,
        items: validItems.map((l) => ({
          product_id: l.product_id,
          quantity: Number(l.quantity),
          unit_price: Number(l.unit_price),
          discount_pct: Number(l.discount_pct || 0),
        })),
      });
      toast.success("Factura de venta creada");
      onSaved();
      handleOpenChange(false);
    } catch (err) {
      const res = (err as { response?: { status?: number; data?: { errors?: ApiErrors; message?: string } } }).response;
      if (res?.status === 422 && res.data?.errors) {
        setErrors(res.data.errors);
        toast.error("Hay campos por corregir.");
      } else if (res?.status === 403) {
        toast.error("Sin permiso para crear facturas de venta.");
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
          <DialogTitle>Nueva factura de venta</DialogTitle>
        </DialogHeader>

        <form id={formId} onSubmit={submit} className="space-y-5">
          <div className="grid gap-4 sm:grid-cols-2">
            {/* Pedido origen */}
            <label className="space-y-1.5 text-sm sm:col-span-2">
              <span className="font-medium">Pedido de venta origen (opcional)</span>
              <select
                value={saleOrderId}
                onChange={(e) => onSoChange(e.target.value ? Number(e.target.value) : "")}
                disabled={loadingData}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-primary disabled:opacity-50"
              >
                <option value="">Sin pedido asociado</option>
                {saleOrders.map((so) => <option key={so.id} value={so.id}>{so.number}</option>)}
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

            {/* Número */}
            <label className="space-y-1.5 text-sm">
              <span className="font-medium">N° de factura <span className="text-destructive">*</span></span>
              <Input
                required
                value={number}
                onChange={(e) => { setNumber(e.target.value); setErrors((p) => ({ ...p, number: [] })); }}
                placeholder="FV-0001"
              />
              {errors.number?.[0] && <span className="text-xs text-destructive">{errors.number[0]}</span>}
            </label>

            {/* Fecha */}
            <label className="space-y-1.5 text-sm">
              <span className="font-medium">Fecha <span className="text-destructive">*</span></span>
              <Input type="date" required value={date} onChange={(e) => setDate(e.target.value)} />
            </label>

            {/* Vencimiento */}
            <label className="space-y-1.5 text-sm">
              <span className="font-medium">Fecha de vencimiento <span className="text-destructive">*</span></span>
              <Input type="date" required value={dueDate} onChange={(e) => setDueDate(e.target.value)} />
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
            {saving ? "Guardando..." : "Crear factura"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

// ── Post action ───────────────────────────────────────────────────────────────

function PostSaleInvoiceButton({ invoice, onDone }: { invoice: SaleInvoice; onDone: () => void }) {
  const [posting, setPosting] = React.useState(false);

  if (invoice.status !== "draft") return null;

  async function doPost() {
    setPosting(true);
    try {
      await api.post(`/sale-invoices/${invoice.id}/post`);
      toast.success("Factura posteada — stock y CxC actualizados");
      onDone();
    } catch (err) {
      const res = (err as { response?: { data?: { message?: string } } }).response;
      toast.error(res?.data?.message ?? "Error al postear la factura.");
    } finally {
      setPosting(false);
    }
  }

  return (
    <Button
      variant="ghost"
      size="sm"
      onClick={doPost}
      disabled={posting}
      className="text-blue-700 hover:text-blue-900 hover:bg-blue-50"
    >
      <CheckCircle className="h-4 w-4" />
      {posting ? "Posteando..." : "Postear"}
    </Button>
  );
}

// ── Page ─────────────────────────────────────────────────────────────────────

const baseColumns: AppColumnDef<SaleInvoice>[] = [
  { accessorKey: "number", header: "Número" },
  { header: "Cliente", cell: ({ row }) => { const c = row.original.client as any; return c?.company_name ?? (`${c?.first_name ?? ""} ${c?.last_name ?? ""}`.trim() || "-"); } },
  { header: "Tipo", cell: ({ row }) => TYPE_LABELS[row.original.type] ?? row.original.type },
  dateColumn<SaleInvoice>("date", "Fecha"),
  dateColumn<SaleInvoice>("due_date", "Vence"),
  { header: "Total", cell: ({ row }) => COP(row.original.total) },
  {
    header: "Estado",
    cell: ({ row }) => (
      <Badge variant={INVOICE_STATUS_VARIANT[row.original.status] ?? badgeVariant(row.original.status)}>{STATUS_LABELS[row.original.status] ?? row.original.status}</Badge>
    ),
  },
];

export default function FacturasVentaPage() {
  const table = useApiTable<SaleInvoice>("/sale-invoices");
  const [modalOpen, setModalOpen] = React.useState(false);

  const columns: AppColumnDef<SaleInvoice>[] = [
    ...baseColumns,
    {
      id: "actions",
      header: "",
      cell: ({ row }) => (
        <div className="flex justify-end">
          <PostSaleInvoiceButton invoice={row.original} onDone={table.refresh} />
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Facturas de venta</h1>
          <p className="text-sm text-muted-foreground">Facturas y notas crédito emitidas a clientes.</p>
        </div>
        <Button onClick={() => setModalOpen(true)}>
          <Plus className="h-4 w-4" /> Nueva factura
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

      <CreateSaleInvoiceModal open={modalOpen} onOpenChange={setModalOpen} onSaved={table.refresh} />
    </div>
  );
}
