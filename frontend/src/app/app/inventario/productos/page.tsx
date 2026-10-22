"use client";

import Image from "next/image";
import { useEffect, useState } from "react";
import { Package, PackageCheck } from "lucide-react";
import { CrudField } from "@/components/crud/crud-modal";
import { KpiCard } from "@/components/kpi-card";
import { ModuleTablePage } from "@/components/module-table-page";
import { Badge, badgeVariant } from "@/components/ui/badge";
import { api } from "@/lib/api";
import { STATUS_OPTIONS } from "@/lib/constants";
import { statusLabel } from "@/lib/labels";
import { AppColumnDef } from "@/lib/table-types";
import { Product } from "@/lib/types";

type ProductStats = { total: number; active: number };

const TYPE_LABEL: Record<Product["type"], string> = {
  storable: "Almacenable", service: "Servicio", consumable: "Consumible",
};

const fmt = new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 });

const columns: AppColumnDef<Product>[] = [
  {
    header: "Foto",
    cell: ({ row }) => {
      const { image_url, name } = row.original;
      return image_url
        ? <Image unoptimized src={image_url} alt={name} width={40} height={40} className="h-10 w-10 rounded object-cover border" />
        : <div className="h-10 w-10 rounded border bg-muted flex items-center justify-center text-xs text-muted-foreground">—</div>;
    },
  },
  { header: "SKU", cell: ({ row }) => row.original.sku ?? "—" },
  { accessorKey: "name", header: "Nombre" },
  { header: "Tipo", cell: ({ row }) => <Badge>{TYPE_LABEL[row.original.type]}</Badge> },
  { header: "Categoria", cell: ({ row }) => row.original.category?.name ?? "—" },
  { header: "Marca", cell: ({ row }) => row.original.brand?.name ?? "—" },
  { header: "Unidad", cell: ({ row }) => row.original.unit?.abbreviation ?? "—" },
  { header: "Costo", cell: ({ row }) => fmt.format(row.original.cost_price) },
  { header: "Precio", cell: ({ row }) => fmt.format(row.original.sale_price) },
  { header: "Estado", cell: ({ row }) => <Badge variant={badgeVariant(row.original.status)}>{statusLabel(row.original.status)}</Badge> },
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
    name: "status", label: "Estado", type: "select", required: true, defaultValue: "active", createHidden: true,
    options: STATUS_OPTIONS,
  },
  { name: "image_url", label: "URL de imagen", type: "url", colSpan: "full" },
  { name: "description", label: "Descripcion", type: "textarea", colSpan: "full" },
];

export default function ProductosPage() {
  const [stats, setStats] = useState<ProductStats | null>(null);
  const [statsStatus, setStatsStatus] = useState<"loading" | "error" | "ready">("loading");

  useEffect(() => {
    api.get<ProductStats>("/products/stats")
      .then((r) => { setStats(r.data); setStatsStatus("ready"); })
      .catch(() => setStatsStatus("error"));
  }, []);

  const statsSlot = (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-2">
      <KpiCard label="Total productos" value={stats?.total ?? null} icon={Package} status={statsStatus} />
      <KpiCard label="Activos" value={stats?.active ?? null} icon={PackageCheck} status={statsStatus} />
    </div>
  );

  return (
    <ModuleTablePage<Product>
      title="Productos"
      description="Catalogo de productos, servicios y consumibles."
      resource="/products"
      exportResource="products"
      columns={columns}
      fields={fields}
      actionLabel="Nuevo producto"
      statsSlot={statsSlot}
    />
  );
}
