"use client";

import { CrudField } from "@/components/crud/crud-modal";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge } from "@/components/ui/badge";
import { STATUS_OPTIONS } from "@/lib/constants";
import { AppColumnDef } from "@/lib/table-types";
import { Product } from "@/lib/types";

const TYPE_LABEL: Record<Product["type"], string> = {
  storable: "Almacenable", service: "Servicio", consumable: "Consumible",
};

const fmt = new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 });

const columns: AppColumnDef<Product>[] = [
  { header: "SKU", cell: ({ row }) => row.original.sku ?? "—" },
  { accessorKey: "name", header: "Nombre" },
  { header: "Tipo", cell: ({ row }) => <Badge>{TYPE_LABEL[row.original.type]}</Badge> },
  { header: "Categoria", cell: ({ row }) => row.original.category?.name ?? "—" },
  { header: "Marca", cell: ({ row }) => row.original.brand?.name ?? "—" },
  { header: "Unidad", cell: ({ row }) => row.original.unit?.abbreviation ?? "—" },
  { header: "Costo", cell: ({ row }) => fmt.format(row.original.cost_price) },
  { header: "Precio", cell: ({ row }) => fmt.format(row.original.sale_price) },
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
  { name: "category_id", label: "Categoria", type: "relation-select", endpoint: "/categories?per_page=100" },
  { name: "brand_id", label: "Marca", type: "relation-select", endpoint: "/brands?per_page=100" },
  { name: "unit_id", label: "Unidad", type: "relation-select", endpoint: "/units?per_page=100" },
  {
    name: "status", label: "Estado", type: "select", required: true, defaultValue: "active",
    options: STATUS_OPTIONS,
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
