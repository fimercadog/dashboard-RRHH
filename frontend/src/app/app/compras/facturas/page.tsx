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
import { api, PaginatedResponse } from "@/lib/api";
import { AppColumnDef, dateColumn } from "@/lib/table-types";
import { PurchaseInvoice, PurchaseOrder, PurchaseReceipt, Supplier } from "@/lib/types";
import { useApiTable } from "@/lib/use-api-table";

const COP = (v: number) =>
  new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(v);

const STATUS_LABELS: Record<string, string> = {
  draft: "Borrador", posted: "Posteada", partially_paid: "Pago parcial", paid: "Pagada", cancelled: "Cancelada",
};
const STATUS_COLOR: Record<string, string> = {
  draft: "bg-muted text-muted-foreground",
  posted: "bg-blue-100 text-blue-800",
  partially_paid: "bg-amber-100 text-amber-800",
  paid: "bg-green-100 text-green-800",
  cancelled: "bg-red-100 text-red-800",
};

type ApiErrors = Record<string, string[]>;

// ── Create Invoice Modal ──────────────────────────────────────────────────────
// PurchaseInvoice NO tiene líneas de producto — es el documento financiero
// que el proveedor emite. Los montos se ingresan directamente.

type CreateInvoiceModalProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSaved: () => void;
};

function CreatePurchaseInvoiceModal({ open, onOpenChange, onSaved }: CreateInvoiceModalProps) {
  const today = new Date().toISOString().slice(0, 10);

  const [supplierId, setSupplierId] = React.useState<number | "">("");
  const [purchaseOrderId, setPurchaseOrderId] = React.useState<number | "">("");
  const [purchaseReceiptId, setPurchaseReceiptId] = React.useState<number | "">("");
  const [number, setNumber] = React.useState("");
  const [date, setDate] = React.useState(today);
  const [dueDate, setDueDate] = React.useState(today);
  const [subtotal, setSubtotal] = React.useState<number | "">(0);
  const [tax, setTax] = React.useState<number | "">(0);
  const [notes, setNotes] = React.useState("");

  const [suppliers, setSuppliers] = React.useState<Supplier[]>([]);
  const [purchaseOrders, setPurchaseOrders] = React.useState<PurchaseOrder[]>([]);
  const [purchaseReceipts, setPurchaseReceipts] = React.useState<PurchaseReceipt[]>([]);
  const [loadingData, setLoadingData] = React.useState(false);

  const [saving, setSaving] = React.useState(false);
  const [errors, setErrors] = React.useState<ApiErrors>({});

  React.useEffect(() => {
    if (!open) return;
    setLoadingData(true);
    Promise.all([
      api.get<PaginatedResponse<Supplier>>("/suppliers", { params: { per_page: 200 } }),
      api.get<PaginatedResponse<PurchaseOrder>>("/purchase-orders", { params: { per_page: 200 } }),
      api.get<PaginatedResponse<PurchaseReceipt>>("/purchase-receipts", { params: { per_page: 200 } }),
    ])
      .then(([s, po, pr]) => {
        setSuppliers(s.data.data);
        setPurchaseOrders(po.data.data);
        setPurchaseReceipts(pr.data.data);
      })
      .catch(() => toast.error("No se pudieron cargar los datos de referencia."))
      .finally(() => setLoadingData(false));
  }, [open]);

  function onPoChange(poId: number | "") {
    setPurchaseOrderId(poId);
    if (poId) {
      const po = purchaseOrders.find((o) => o.id === poId);
      if (po?.supplier?.id) setSupplierId(po.supplier.id);
    }
  }

  function resetForm() {
    setSupplierId(""); setPurchaseOrderId(""); setPurchaseReceiptId("");
    setNumber(""); setDate(today); setDueDate(today); setSubtotal(0); setTax(0); setNotes(""); setErrors({});
  }

  function handleOpenChange(next: boolean) {
    if (!next) resetForm();
    onOpenChange(next);
  }

  const total = Number(subtotal || 0) + Number(tax || 0);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setSaving(true);
    setErrors({});
    try {
      await api.post("/purchase-invoices", {
        supplier_id: supplierId || null,
        purchase_order_id: purchaseOrderId || null,
        purchase_receipt_id: purchaseReceiptId || null,
        number,
        date,
        due_date: dueDate,
        subtotal: Number(subtotal || 0),
        tax: Number(tax || 0),
        total,
        notes: notes || null,
      });
      toast.success("Factura de compra creada");
      onSaved();
      handleOpenChange(false);
    } catch (err) {
      const res = (err as { response?: { status?: number; data?: { errors?: ApiErrors; message?: string } } }).response;
      if (res?.status === 422 && res.data?.errors) {
        setErrors(res.data.errors);
        toast.error("Hay campos por corregir.");
      } else if (res?.status === 403) {
        toast.error("Sin permiso para crear facturas de compra.");
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
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Nueva factura de compra</DialogTitle>
        </DialogHeader>

        <form id={formId} onSubmit={submit} className="space-y-5">
          <div className="grid gap-4 sm:grid-cols-2">
            {/* OC origen */}
            <label className="space-y-1.5 text-sm">
              <span className="font-medium">Orden de compra (opcional)</span>
              <select
                value={purchaseOrderId}
                onChange={(e) => onPoChange(e.target.value ? Number(e.target.value) : "")}
                disabled={loadingData}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-primary disabled:opacity-50"
              >
                <option value="">Sin OC asociada</option>
                {purchaseOrders.map((po) => <option key={po.id} value={po.id}>{po.number}</option>)}
              </select>
            </label>

            {/* Recepción origen */}
            <label className="space-y-1.5 text-sm">
              <span className="font-medium">Recepción (opcional)</span>
              <select
                value={purchaseReceiptId}
                onChange={(e) => setPurchaseReceiptId(e.target.value ? Number(e.target.value) : "")}
                disabled={loadingData}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-primary disabled:opacity-50"
              >
                <option value="">Sin recepción asociada</option>
                {purchaseReceipts.map((r) => <option key={r.id} value={r.id}>{r.number}</option>)}
              </select>
            </label>

            {/* Proveedor */}
            <label className="space-y-1.5 text-sm">
              <span className="font-medium">Proveedor <span className="text-destructive">*</span></span>
              <select
                required
                value={supplierId}
                onChange={(e) => { setSupplierId(e.target.value ? Number(e.target.value) : ""); setErrors((p) => ({ ...p, supplier_id: [] })); }}
                disabled={loadingData}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-primary disabled:opacity-50"
              >
                <option value="">{loadingData ? "Cargando..." : "Seleccionar proveedor"}</option>
                {suppliers.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
              </select>
              {errors.supplier_id?.[0] && <span className="text-xs text-destructive">{errors.supplier_id[0]}</span>}
            </label>

            {/* Número de factura */}
            <label className="space-y-1.5 text-sm">
              <span className="font-medium">N° de factura proveedor <span className="text-destructive">*</span></span>
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

            {/* Subtotal */}
            <label className="space-y-1.5 text-sm">
              <span className="font-medium">Subtotal <span className="text-destructive">*</span></span>
              <Input
                type="number"
                min="0"
                step="0.01"
                required
                value={subtotal}
                onChange={(e) => { setSubtotal(e.target.value === "" ? "" : Number(e.target.value)); setErrors((p) => ({ ...p, subtotal: [] })); }}
                className="text-right tabular-nums"
              />
              {errors.subtotal?.[0] && <span className="text-xs text-destructive">{errors.subtotal[0]}</span>}
            </label>

            {/* IVA */}
            <label className="space-y-1.5 text-sm">
              <span className="font-medium">IVA / Impuesto</span>
              <Input
                type="number"
                min="0"
                step="0.01"
                value={tax}
                onChange={(e) => setTax(e.target.value === "" ? "" : Number(e.target.value))}
                className="text-right tabular-nums"
              />
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

          {/* Total */}
          <div className="flex justify-end gap-8 border-t pt-3 text-sm font-semibold">
            <span>Total</span>
            <span className="tabular-nums w-36 text-right">{COP(total)}</span>
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

function PostInvoiceButton({ invoice, onDone }: { invoice: PurchaseInvoice; onDone: () => void }) {
  const [posting, setPosting] = React.useState(false);

  if (invoice.status !== "draft") return null;

  async function doPost() {
    setPosting(true);
    try {
      await api.post(`/purchase-invoices/${invoice.id}/post`);
      toast.success("Factura posteada — CxP creada");
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

const columns: AppColumnDef<PurchaseInvoice>[] = [
  { accessorKey: "number", header: "Número" },
  { header: "Proveedor", cell: ({ row }) => row.original.supplier?.name ?? "-" },
  dateColumn<PurchaseInvoice>("date", "Fecha"),
  dateColumn<PurchaseInvoice>("due_date", "Vencimiento"),
  { header: "Total", cell: ({ row }) => COP(row.original.total) },
  {
    header: "Estado",
    cell: ({ row }) => (
      <Badge className={STATUS_COLOR[row.original.status] ?? ""}>{STATUS_LABELS[row.original.status] ?? row.original.status}</Badge>
    ),
  },
];

export default function FacturasCompraPage() {
  const table = useApiTable<PurchaseInvoice>("/purchase-invoices");
  const [modalOpen, setModalOpen] = React.useState(false);

  const tableColumns: AppColumnDef<PurchaseInvoice>[] = [
    ...columns,
    {
      id: "actions",
      header: "",
      cell: ({ row }) => (
        <div className="flex justify-end">
          <PostInvoiceButton invoice={row.original} onDone={table.refresh} />
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Facturas de compra</h1>
          <p className="text-sm text-muted-foreground">Facturas recibidas de proveedores.</p>
        </div>
        <Button onClick={() => setModalOpen(true)}>
          <Plus className="h-4 w-4" /> Nueva factura
        </Button>
      </div>

      <DataTable
        columns={tableColumns}
        data={table.data}
        search={table.search}
        onSearchChange={table.setSearch}
        page={table.page}
        onPageChange={table.setPage}
        loading={table.loading}
        error={table.error}
      />

      <CreatePurchaseInvoiceModal open={modalOpen} onOpenChange={setModalOpen} onSaved={table.refresh} />
    </div>
  );
}
