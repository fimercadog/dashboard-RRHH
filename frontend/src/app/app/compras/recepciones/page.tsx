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
  newPurchaseLine,
  ProductOption,
  PurchaseLine,
  PurchaseLineItemsEditor,
} from "@/components/documents/line-items-editor";
import { api, PaginatedResponse } from "@/lib/api";
import { AppColumnDef, dateColumn } from "@/lib/table-types";
import { PurchaseOrder, PurchaseReceipt, Supplier, Warehouse } from "@/lib/types";
import { useApiTable } from "@/lib/use-api-table";

const COP = (v: number) =>
  new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(v);

const STATUS_LABELS: Record<string, string> = { draft: "Borrador", posted: "Posteada", cancelled: "Cancelada" };
const STATUS_COLOR: Record<string, string> = {
  draft: "bg-muted text-muted-foreground",
  posted: "bg-green-100 text-green-800",
  cancelled: "bg-red-100 text-red-800",
};

type ApiErrors = Record<string, string[]>;

// ── Create Receipt Modal ─────────────────────────────────────────────────────

type CreateReceiptModalProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSaved: () => void;
};

function CreateReceiptModal({ open, onOpenChange, onSaved }: CreateReceiptModalProps) {
  const today = new Date().toISOString().slice(0, 10);

  const [purchaseOrderId, setPurchaseOrderId] = React.useState<number | "">("");
  const [supplierId, setSupplierId] = React.useState<number | "">("");
  const [warehouseId, setWarehouseId] = React.useState<number | "">("");
  const [date, setDate] = React.useState(today);
  const [type, setType] = React.useState<"receipt" | "return">("receipt");
  const [notes, setNotes] = React.useState("");
  const [items, setItems] = React.useState<PurchaseLine[]>([newPurchaseLine()]);

  const [suppliers, setSuppliers] = React.useState<Supplier[]>([]);
  const [warehouses, setWarehouses] = React.useState<Warehouse[]>([]);
  const [products, setProducts] = React.useState<ProductOption[]>([]);
  const [purchaseOrders, setPurchaseOrders] = React.useState<PurchaseOrder[]>([]);
  const [loadingData, setLoadingData] = React.useState(false);

  const [saving, setSaving] = React.useState(false);
  const [errors, setErrors] = React.useState<ApiErrors>({});

  React.useEffect(() => {
    if (!open) return;
    setLoadingData(true);
    Promise.all([
      api.get<PaginatedResponse<Supplier>>("/suppliers", { params: { per_page: 200 } }),
      api.get<PaginatedResponse<Warehouse>>("/warehouses", { params: { per_page: 100 } }),
      api.get<PaginatedResponse<ProductOption>>("/products", { params: { per_page: 500 } }),
      api.get<PaginatedResponse<PurchaseOrder>>("/purchase-orders", { params: { per_page: 200 } }),
    ])
      .then(([s, w, p, po]) => {
        setSuppliers(s.data.data);
        setWarehouses(w.data.data);
        setProducts(p.data.data);
        setPurchaseOrders(po.data.data);
      })
      .catch(() => toast.error("No se pudieron cargar los datos de referencia."))
      .finally(() => setLoadingData(false));
  }, [open]);

  // When a PO is selected, pre-fill supplier
  function onPoChange(poId: number | "") {
    setPurchaseOrderId(poId);
    if (poId) {
      const po = purchaseOrders.find((o) => o.id === poId);
      if (po?.supplier?.id) setSupplierId(po.supplier.id);
    }
  }

  function resetForm() {
    setPurchaseOrderId(""); setSupplierId(""); setWarehouseId("");
    setDate(today); setType("receipt"); setNotes(""); setItems([newPurchaseLine()]); setErrors({});
  }

  function handleOpenChange(next: boolean) {
    if (!next) resetForm();
    onOpenChange(next);
  }

  const validItems = items.filter((l) => l.product_id !== "" && Number(l.quantity) > 0);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    if (!supplierId) { setErrors({ supplier_id: ["El proveedor es requerido."] }); return; }
    if (!warehouseId) { setErrors({ warehouse_id: ["La bodega es requerida."] }); return; }
    if (validItems.length === 0) { setErrors({ items: ["Se requiere al menos una línea válida."] }); return; }

    setSaving(true);
    setErrors({});
    try {
      await api.post("/purchase-receipts", {
        purchase_order_id: purchaseOrderId || null,
        supplier_id: supplierId,
        warehouse_id: warehouseId,
        date,
        type,
        notes: notes || null,
        items: validItems.map((l) => ({
          product_id: l.product_id,
          quantity: Number(l.quantity),
          unit_cost: Number(l.unit_cost),
        })),
      });
      toast.success("Recepción creada");
      onSaved();
      handleOpenChange(false);
    } catch (err) {
      const res = (err as { response?: { status?: number; data?: { errors?: ApiErrors; message?: string } } }).response;
      if (res?.status === 422 && res.data?.errors) {
        setErrors(res.data.errors);
        toast.error("Hay campos por corregir.");
      } else if (res?.status === 403) {
        toast.error("Sin permiso para crear recepciones.");
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
          <DialogTitle>Nueva recepción de mercancía</DialogTitle>
        </DialogHeader>

        <form id={formId} onSubmit={submit} className="space-y-5">
          <div className="grid gap-4 sm:grid-cols-2">
            {/* OC origen (opcional) */}
            <label className="space-y-1.5 text-sm sm:col-span-2">
              <span className="font-medium">Orden de compra origen (opcional)</span>
              <select
                value={purchaseOrderId}
                onChange={(e) => onPoChange(e.target.value ? Number(e.target.value) : "")}
                disabled={loadingData}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-primary disabled:opacity-50"
              >
                <option value="">Sin OC asociada</option>
                {purchaseOrders.map((po) => (
                  <option key={po.id} value={po.id}>{po.number} — {po.supplier?.name}</option>
                ))}
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

            {/* Tipo */}
            <label className="space-y-1.5 text-sm">
              <span className="font-medium">Tipo <span className="text-destructive">*</span></span>
              <select
                required
                value={type}
                onChange={(e) => setType(e.target.value as "receipt" | "return")}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:border-primary"
              >
                <option value="receipt">Recepción</option>
                <option value="return">Devolución</option>
              </select>
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
            <p className="text-sm font-medium">Productos recibidos <span className="text-destructive">*</span></p>
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
        </form>

        <DialogFooter>
          <Button type="button" variant="outline" onClick={() => handleOpenChange(false)} disabled={saving}>
            Cancelar
          </Button>
          <Button type="submit" form={formId} disabled={saving || loadingData}>
            {saving ? "Guardando..." : "Crear recepción"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

// ── Post action ───────────────────────────────────────────────────────────────

function PostReceiptButton({ receipt, onDone }: { receipt: PurchaseReceipt; onDone: () => void }) {
  const [posting, setPosting] = React.useState(false);

  if (receipt.status !== "draft") return null;

  async function doPost() {
    setPosting(true);
    try {
      await api.post(`/purchase-receipts/${receipt.id}/post`);
      toast.success("Recepción posteada — stock actualizado");
      onDone();
    } catch (err) {
      const res = (err as { response?: { data?: { message?: string } } }).response;
      toast.error(res?.data?.message ?? "Error al postear la recepción.");
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
      className="text-green-700 hover:text-green-900 hover:bg-green-50"
    >
      <CheckCircle className="h-4 w-4" />
      {posting ? "Posteando..." : "Postear"}
    </Button>
  );
}

// ── Page ─────────────────────────────────────────────────────────────────────

export default function RecepcionesPage() {
  const table = useApiTable<PurchaseReceipt>("/purchase-receipts");
  const [modalOpen, setModalOpen] = React.useState(false);

  const columns: AppColumnDef<PurchaseReceipt>[] = [
    { accessorKey: "number", header: "Número" },
    { header: "Proveedor", cell: ({ row }) => row.original.supplier?.name ?? "-" },
    { header: "Bodega", cell: ({ row }) => row.original.warehouse?.name ?? "-" },
    dateColumn<PurchaseReceipt>("date", "Fecha"),
    { header: "Tipo", cell: ({ row }) => row.original.type === "receipt" ? "Recepción" : "Devolución" },
    {
      header: "Estado",
      cell: ({ row }) => (
        <Badge className={STATUS_COLOR[row.original.status] ?? ""}>{STATUS_LABELS[row.original.status] ?? row.original.status}</Badge>
      ),
    },
    {
      id: "actions",
      header: "",
      cell: ({ row }) => (
        <div className="flex justify-end">
          <PostReceiptButton receipt={row.original} onDone={table.refresh} />
        </div>
      ),
    },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">Recepciones</h1>
          <p className="text-sm text-muted-foreground">Recepciones y devoluciones de mercancía.</p>
        </div>
        <Button onClick={() => setModalOpen(true)}>
          <Plus className="h-4 w-4" /> Nueva recepción
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

      <CreateReceiptModal open={modalOpen} onOpenChange={setModalOpen} onSaved={table.refresh} />
    </div>
  );
}
