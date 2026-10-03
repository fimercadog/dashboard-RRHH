"use client";

import { ModuleTablePage } from "@/components/module-table-page";
import { AppColumnDef } from "@/lib/table-types";
import { ProductStock } from "@/lib/types";

const fmt = new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 });

const columns: AppColumnDef<ProductStock>[] = [
  { header: "Producto", cell: ({ row }) => row.original.product?.name ?? "—" },
  { header: "SKU", cell: ({ row }) => row.original.product?.sku ?? "—" },
  { header: "Bodega", cell: ({ row }) => row.original.warehouse?.name ?? "—" },
  { header: "Cantidad", cell: ({ row }) => row.original.quantity },
  { header: "Stock min.", cell: ({ row }) => row.original.product?.min_stock ?? "—" },
  { header: "Costo prom.", cell: ({ row }) => row.original.avg_cost ? fmt.format(row.original.avg_cost) : "—" },
];

export default function StockPage() {
  return (
    <ModuleTablePage<ProductStock>
      title="Stock"
      description="Niveles de inventario por producto y bodega."
      resource="/stock"
      columns={columns}
      fields={[]}
    />
  );
}
