"use client";

import { ModuleTablePage } from "@/components/module-table-page";
import { Badge } from "@/components/ui/badge";
import { AppColumnDef } from "@/lib/table-types";
import { StockMovement } from "@/lib/types";

const TYPE_LABEL: Record<StockMovement["type"], string> = {
  entry: "Entrada", exit: "Salida",
  transfer_out: "Traslado salida", transfer_in: "Traslado entrada",
  adjustment: "Ajuste", opening: "Apertura",
};

const fmt = new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 });

const columns: AppColumnDef<StockMovement>[] = [
  { header: "Tipo", cell: ({ row }) => <Badge variant="outline">{TYPE_LABEL[row.original.type]}</Badge> },
  { header: "Producto", cell: ({ row }) => row.original.product?.name ?? "—" },
  { header: "Bodega", cell: ({ row }) => row.original.warehouse?.name ?? "—" },
  { header: "Cantidad", cell: ({ row }) => row.original.quantity },
  { header: "Costo unit.", cell: ({ row }) => row.original.unit_cost ? fmt.format(row.original.unit_cost) : "—" },
  { header: "Total", cell: ({ row }) => row.original.total_cost ? fmt.format(row.original.total_cost) : "—" },
  { header: "Referencia", cell: ({ row }) => row.original.reference_type ? `${row.original.reference_type} #${row.original.reference_id}` : "—" },
  { header: "Registrado por", cell: ({ row }) => row.original.user?.name ?? "—" },
  { header: "Fecha", cell: ({ row }) => new Date(row.original.created_at).toLocaleDateString("es-CO", { day: "2-digit", month: "short", year: "numeric" }) },
];

export default function MovimientosPage() {
  return (
    <ModuleTablePage<StockMovement>
      title="Movimientos de inventario"
      description="Historial de entradas, salidas, traslados y ajustes."
      resource="/stock-movements"
      columns={columns}
      fields={[]}
    />
  );
}
