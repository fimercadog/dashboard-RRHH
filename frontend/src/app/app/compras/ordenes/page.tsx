"use client";

import * as React from "react";
import { Plus } from "lucide-react";
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
  newPurchaseLine,
  ProductOption,
  PurchaseLine,
  PurchaseLineItemsEditor,
  purchaseLineSubtotal,
} from "@/components/documents/line-items-editor";
import { api, PaginatedResponse } from "@/lib/api";
import { AppColumnDef, dateColumn } from "@/lib/table-types";
import { PurchaseOrder, Supplier, Warehouse } from "@/lib/types";
import { useApiTable } from "@/lib/use-api-table";

// ── Helpers ─────────────────────────────────────────────────────────────────

const COP = (v: number) =>
  new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(v);

const STATUS_LABELS: Record<string, string> = {
  draft: "Borrador", sent: "Enviada", partial: "Parcial", received: "Recibida", cancelled: "Cancelada",
};


const columns: AppColumnDef<PurchaseOrder>[] = [
  { accessorKey: "number", header: "Número" },
  { header: "Proveedor", cell: ({ row }) => row.original.supplier?.name ?? "-" },
  dateColumn<PurchaseOrder>("date", "Fecha"),
  dateColumn<PurchaseOrder>("expected_date", "Fecha esperada"),
  { header: "Total", cell: ({ row }) => COP(row.original.total) },
  {
    header: "Estado",
    cell: ({ row }) => (
      <Badge variant={badgeVariant(row.original.status)}>{STATUS_LABELS[row.original.status] ?? row.original.status}</Badge>
    ),
  },
];

// ── Create OC Modal ──────────────────────────────────────────────────────────

type ApiErrors = Record<string, string[]>;

type CreateOCModalProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSaved: () => void;
};

function CreateOCModal({ open, onOpenChange, onSaved }: CreateOCModalProps) {
  const today = new Date().toISOString().slice(0, 10);

  const [supplierId, setSupplierId] = React.useState<number | "">("");
  const [warehouseId, setWarehouseId] = React.useState<number | "">("");
  const [date, setDate] = React.useState(today);
  const [expectedDate, setExpectedDate] = React.useState("");
  const [taxAmt, setTaxAmt] = React.useState<number | "">(0);
  const [notes, setNotes] = React.useState("");
  const [items, setItems] = React.useState<PurchaseLine[]>([newPurchaseLine()]);

  const [suppliers, setSuppliers] = React.useState<Supplier[]>([]);
  const [warehouses, setWarehouses] = React.useState<Warehouse[]>([]);
  const [products, setProducts] = React.useState<ProductOption[]>([]);
  const [loadingData, setLoadingData] = React.useState(false);

  const [saving, setSaving] = React.useState(false);
  const [errors, setErrors] = React.useState<ApiErrors>({});

  // Load reference data when modal opens
  React.useEffect(() => {
    if (!open) return;
    setLoadingData(true);
    Promise.all([
      api.get<PaginatedResponse<Supplier>>("/suppliers", { params: { per_page: 200 } }),
      api.get<PaginatedResponse<Warehouse>>("/warehouses", { params: { per_page: 100 } }),
      api.get<PaginatedResponse<ProductOption>>("/products", { params: { per_page: 500 } }),
    ])
      .then(([s, w, p]) => {
        setSuppliers(s.data.data);
        setWarehouses(w.data.data);
        setProducts(p.data.data);
      })
      .catch(() => toast.error("No se pudieron cargar los datos de referencia."))
      .finally(() => setLoadingData(false));
  }, [open]);

  function resetForm() {
    setSupplierId(""); setWarehouseId(""); setDate(today); setExpectedDate("");
    setTaxAmt(0); setNotes(""); setItems([newPurchaseLine()]); setErrors({});
  }

  function handleOpenChange(next: boolean) {
    if (!next) resetForm();
    onOpenChange(next);
  }

  const lineSubtotal = items.reduce((s, l) => s + purchaseLineSubtotal(l), 0);
  const total = lineSubtotal + Number(taxAmt || 0);

  const validItems = items.filter((l) => l.product_id !== "" && Number(l.quantity) > 0);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    if (!supplierId) { setErrors({ supplier_id: ["El proveedor es requerido."] }); return; }
    if (!warehouseId) { setErrors({ warehouse_id: ["La bodega es requerida."] }); return; }
    if (validItems.length === 0) { setErrors({ items: ["Se requiere al menos una línea válida."] }); return; }

    setSaving(true);
    setErrors({});
    try {
      await api.post("/purchase-orders", {
        supplier_id: supplierId,
        warehouse_id: warehouseId,
        date,
        expected_date: expectedDate || null,
        tax: Number(taxAmt || 0),
        notes: notes || null,
        items: validItems.map((l) => ({
          product_id: l.product_id,
          quantity: Number(l.quantity),
          unit_cost: Number(l.unit_cost),
        })),
      });
      toast.success("Orden de compra creada");
      onSaved();
      handleOpenChange(false);
    } catch (err) {
      const res = (err as { response?: { status?: number; data?: { errors?: ApiErrors; message?: string } } }).response;
      if (res?.status === 422 && res.data?.errors) {
        setErrors(res.data.errors);
        toast.error("Hay campos por corregir.");
      } else if (res?.status === 403) {
        toast.error("Sin permiso para crear órdenes de compra.");
      } else {
        toast.error(res?.data?.message ?? "Error al guardar. Revisa los campos.");
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
          <DialogTitle>Nueva orden de compra</DialogTitle>
        </DialogHeader>

        <form id={formId} onSubmit={submit} className="space-y-5">
          {/* Header fields */}
          <div className="grid gap-4 sm:grid-cols-2">
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

            {/* Bodega */}
            <label className="space-y-1.5 text-sm">
              <span className="font-medium">Bodega destino <span className="text-destructive">*</span></span>
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

            {/* Fecha esperada */}
            <label className="space-y-1.5 text-sm">
              <span className="font-medium">Fecha esperada</span>
              <Input type="date" value={expectedDate} onChange={(e) => setExpectedDate(e.target.value)} />
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
            <PurchaseLineItemsEditor
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
          <div className="flex flex-col items-end gap-1 border-t pt-3 text-sm">
            <div className="flex gap-8">
              <span className="text-muted-foreground">Subtotal</span>
              <span className="tabular-nums w-28 text-right">{COP(lineSubtotal)}</span>
            </div>
            <div className="flex items-center gap-4">
              <span className="text-muted-foreground">IVA / Impuesto</span>
              <Input
                type="number"
                min="0"
                step="0.01"
                value={taxAmt}
                onChange={(e) => setTaxAmt(e.target.value === "" ? "" : Number(e.target.value))}
                className="h-8 w-28 text-right tabular-nums"
              />
            </div>
            <div className="flex gap-8 font-semibold">
              <span>Total</span>
              <span className="tabular-nums w-28 text-right">{COP(total)}</span>
            </div>
          </div>
        </form>

        <DialogFooter>
          <Button type="button" variant="outline" onClick={() => handleOpenChange(false)} disabled={saving}>
            Cancelar
          </Button>
          <Button type="submit" form={formId} disabled={saving || loadingData}>
            {saving ? "Guardando..." : "Crear orden"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

// ── Page ─────────────────────────────────────────────────────────────────────

export default function OrdenesPage() {
  const table = useApiTable<PurchaseOrder>("/purchase-orders");
  const [modalOpen, setModalOpen] = React.useState(false);

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Órdenes de compra</h1>
          <p className="text-sm text-muted-foreground">Órdenes de compra emitidas a proveedores.</p>
        </div>
        <Button onClick={() => setModalOpen(true)}>
          <Plus className="h-4 w-4" /> Nueva orden
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

      <CreateOCModal open={modalOpen} onOpenChange={setModalOpen} onSaved={table.refresh} />
    </div>
  );
}
