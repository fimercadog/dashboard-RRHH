"use client";

import { CrudField } from "@/components/crud/crud-modal";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge } from "@/components/ui/badge";
import { AppColumnDef } from "@/lib/table-types";
import { Product } from "@/lib/types";

const TYPE_LABEL: Record<Product["type"], string> = {
  storable: "Almacenable", service: "Servicio", consumable: "Consumible",
};

const columns: AppColumnDef<Product>[] = [
  { header: "SKU", cell: ({ row }) => row.original.sku ?? "—" },
  { accessorKey: "name", header: "Nombre" },
  { header: "Tipo", cell: ({ row }) => <Badge variant="outline">{TYPE_LABEL[row.original.type]}</Badge> },
  { header: "Categoria", cell: ({ row }) => row.original.category?.name ?? "—" },
  { header: "Marca", cell: ({ row }) => row.original.brand?.name ?? "—" },
  { header: "Unidad", cell: ({ row }) => row.original.unit?.abbreviation ?? "—" },
  { header: "Costo", cell: ({ row }) => new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(row.original.cost_price) },
  { header: "Precio", cell: ({ row }) => new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(row.original.sale_price) },
  { header: "Estado", cell: ({ row }) => <Badge>{row.original.status}</Badge> },
];

const fields: CrudField[] = [
  { name: "name", label: "Nombre", required: true },
  { name: "sku", label: "SKU" },
  {
    name: "type", label: "Tipo", type: "select", required: true,
    options: [
      { label: "Almacenable", value: "storable" },
      { label: "Servicio", value: "service" },
      { label: "Consumible", value: "consumable" },
    ],
  },
  { name: "cost_price", label: "Costo (COP)", type: "number", required: true },
  { name: "sale_price", label: "Precio venta (COP)", type: "number", required: true },
  { name: "min_stock", label: "Stock minimo", type: "number" },
  { name: "category_id", label: "ID Categoria", type: "number" },
  { name: "brand_id", label: "ID Marca", type: "number" },
  { name: "unit_id", label: "ID Unidad", type: "number" },
  {
    name: "status", label: "Estado", type: "select", required: true,
    options: [{ label: "Activo", value: "active" }, { label: "Inactivo", value: "inactive" }],
  },
  { name: "description", label: "Descripcion", type: "textarea", colSpan: "full" },
];

export default function ProductosPage() {
  return (
    <ModuleTablePage<Product>
      title="Productos"
      description="Catalogo de productos, servicios y consumibles."
      resource="/products"
      exportResource="products"
      columns={columns}
      fields={fields}
      actionLabel="Nuevo producto"
    />
  );
}
