"use client";

import * as React from "react";
import { Plus, Trash2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

export type ProductOption = { id: number; sku?: string; name: string; cost_price: number; sale_price: number };

// ── Purchase variant ───────────────────────────────────────────────────────
export type PurchaseLine = {
  _key: number;
  product_id: number | "";
  quantity: number | "";
  unit_cost: number | "";
};

// ── Sale variant ───────────────────────────────────────────────────────────
export type SaleLine = {
  _key: number;
  product_id: number | "";
  quantity: number | "";
  unit_price: number | "";
  discount_pct: number | "";
};

let _keySeq = 1;
export const newPurchaseLine = (): PurchaseLine => ({ _key: _keySeq++, product_id: "", quantity: "", unit_cost: "" });
export const newSaleLine     = (): SaleLine     => ({ _key: _keySeq++, product_id: "", quantity: "", unit_price: "", discount_pct: "" });

export function purchaseLineSubtotal(l: PurchaseLine): number {
  return Number(l.quantity || 0) * Number(l.unit_cost || 0);
}

export function saleLineSubtotal(l: SaleLine): number {
  const gross = Number(l.quantity || 0) * Number(l.unit_price || 0);
  return gross * (1 - Number(l.discount_pct || 0) / 100);
}

const COP = (v: number) =>
  new Intl.NumberFormat("es-CO", { style: "currency", currency: "COP", maximumFractionDigits: 0 }).format(v);

// ── Shared cell styles ─────────────────────────────────────────────────────
const tdClass = "px-2 py-1.5 align-top";
const inputClass = "h-8 text-sm";

// ══ Purchase Line Items Editor ═════════════════════════════════════════════

type PurchaseEditorProps = {
  items: PurchaseLine[];
  products: ProductOption[];
  onChange: (items: PurchaseLine[]) => void;
  errors?: Record<string, string>;
};

export function PurchaseLineItemsEditor({ items, products, onChange, errors = {} }: PurchaseEditorProps) {
  function updateLine(key: number, patch: Partial<PurchaseLine>) {
    onChange(items.map((l) => (l._key === key ? { ...l, ...patch } : l)));
  }

  function removeLine(key: number) {
    onChange(items.filter((l) => l._key !== key));
  }

  function addLine() {
    onChange([...items, newPurchaseLine()]);
  }

  function onProductChange(key: number, productId: number | "") {
    if (!productId) { updateLine(key, { product_id: "" }); return; }
    const product = products.find((p) => p.id === productId);
    updateLine(key, { product_id: productId, unit_cost: product?.cost_price ?? "" });
  }

  const subtotal = items.reduce((s, l) => s + purchaseLineSubtotal(l), 0);

  return (
    <div className="space-y-2">
      <div className="overflow-x-auto -mx-1">
        <table className="w-full min-w-[560px] text-sm">
          <thead>
            <tr className="border-b text-muted-foreground text-xs">
              <th className="px-2 pb-1.5 text-left font-medium w-[40%]">Producto *</th>
              <th className="px-2 pb-1.5 text-left font-medium w-[18%]">Cantidad *</th>
              <th className="px-2 pb-1.5 text-left font-medium w-[22%]">Costo unit. *</th>
              <th className="px-2 pb-1.5 text-right font-medium w-[15%]">Subtotal</th>
              <th className="w-8" />
            </tr>
          </thead>
          <tbody className="divide-y">
            {items.map((line, idx) => {
              const errPfx = `items.${idx}`;
              return (
                <tr key={line._key} className="group">
                  <td className={tdClass}>
                    <select
                      value={line.product_id}
                      required
                      onChange={(e) => onProductChange(line._key, e.target.value ? Number(e.target.value) : "")}
                      className="flex h-8 w-full rounded-md border border-input bg-background px-2 text-sm outline-none focus-visible:border-primary"
                    >
                      <option value="">— producto —</option>
                      {products.map((p) => (
                        <option key={p.id} value={p.id}>{p.sku ? `[${p.sku}] ` : ""}{p.name}</option>
                      ))}
                    </select>
                    {(errors[`${errPfx}.product_id`]) && (
                      <span className="text-xs text-destructive">{errors[`${errPfx}.product_id`]}</span>
                    )}
                  </td>
                  <td className={tdClass}>
                    <Input
                      type="number"
                      min="0.001"
                      step="0.001"
                      required
                      value={line.quantity}
                      onChange={(e) => updateLine(line._key, { quantity: e.target.value === "" ? "" : Number(e.target.value) })}
                      className={inputClass}
                    />
                    {errors[`${errPfx}.quantity`] && (
                      <span className="text-xs text-destructive">{errors[`${errPfx}.quantity`]}</span>
                    )}
                  </td>
                  <td className={tdClass}>
                    <Input
                      type="number"
                      min="0"
                      step="0.01"
                      required
                      value={line.unit_cost}
                      onChange={(e) => updateLine(line._key, { unit_cost: e.target.value === "" ? "" : Number(e.target.value) })}
                      className={inputClass}
                    />
                    {errors[`${errPfx}.unit_cost`] && (
                      <span className="text-xs text-destructive">{errors[`${errPfx}.unit_cost`]}</span>
                    )}
                  </td>
                  <td className={`${tdClass} text-right tabular-nums`}>
                    {COP(purchaseLineSubtotal(line))}
                  </td>
                  <td className={tdClass}>
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      className="h-8 w-8 p-0 text-muted-foreground hover:text-destructive"
                      onClick={() => removeLine(line._key)}
                      disabled={items.length === 1}
                      aria-label="Eliminar línea"
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      <div className="flex items-center justify-between pt-1">
        <Button type="button" variant="outline" size="sm" onClick={addLine}>
          <Plus className="h-3.5 w-3.5" /> Agregar línea
        </Button>
        <span className="text-sm font-medium">Subtotal: {COP(subtotal)}</span>
      </div>
    </div>
  );
}

// ══ Sale Line Items Editor ═════════════════════════════════════════════════

type SaleEditorProps = {
  items: SaleLine[];
  products: ProductOption[];
  onChange: (items: SaleLine[]) => void;
  errors?: Record<string, string>;
};

export function SaleLineItemsEditor({ items, products, onChange, errors = {} }: SaleEditorProps) {
  function updateLine(key: number, patch: Partial<SaleLine>) {
    onChange(items.map((l) => (l._key === key ? { ...l, ...patch } : l)));
  }

  function removeLine(key: number) {
    onChange(items.filter((l) => l._key !== key));
  }

  function addLine() {
    onChange([...items, newSaleLine()]);
  }

  function onProductChange(key: number, productId: number | "") {
    if (!productId) { updateLine(key, { product_id: "" }); return; }
    const product = products.find((p) => p.id === productId);
    updateLine(key, { product_id: productId, unit_price: product?.sale_price ?? "" });
  }

  const subtotal = items.reduce((s, l) => s + saleLineSubtotal(l), 0);

  return (
    <div className="space-y-2">
      <div className="overflow-x-auto -mx-1">
        <table className="w-full min-w-[640px] text-sm">
          <thead>
            <tr className="border-b text-muted-foreground text-xs">
              <th className="px-2 pb-1.5 text-left font-medium w-[35%]">Producto *</th>
              <th className="px-2 pb-1.5 text-left font-medium w-[15%]">Cantidad *</th>
              <th className="px-2 pb-1.5 text-left font-medium w-[20%]">Precio unit. *</th>
              <th className="px-2 pb-1.5 text-left font-medium w-[13%]">Desc. %</th>
              <th className="px-2 pb-1.5 text-right font-medium w-[12%]">Subtotal</th>
              <th className="w-8" />
            </tr>
          </thead>
          <tbody className="divide-y">
            {items.map((line, idx) => {
              const errPfx = `items.${idx}`;
              return (
                <tr key={line._key} className="group">
                  <td className={tdClass}>
                    <select
                      value={line.product_id}
                      required
                      onChange={(e) => onProductChange(line._key, e.target.value ? Number(e.target.value) : "")}
                      className="flex h-8 w-full rounded-md border border-input bg-background px-2 text-sm outline-none focus-visible:border-primary"
                    >
                      <option value="">— producto —</option>
                      {products.map((p) => (
                        <option key={p.id} value={p.id}>{p.sku ? `[${p.sku}] ` : ""}{p.name}</option>
                      ))}
                    </select>
                    {errors[`${errPfx}.product_id`] && (
                      <span className="text-xs text-destructive">{errors[`${errPfx}.product_id`]}</span>
                    )}
                  </td>
                  <td className={tdClass}>
                    <Input
                      type="number"
                      min="0.0001"
                      step="0.001"
                      required
                      value={line.quantity}
                      onChange={(e) => updateLine(line._key, { quantity: e.target.value === "" ? "" : Number(e.target.value) })}
                      className={inputClass}
                    />
                    {errors[`${errPfx}.quantity`] && (
                      <span className="text-xs text-destructive">{errors[`${errPfx}.quantity`]}</span>
                    )}
                  </td>
                  <td className={tdClass}>
                    <Input
                      type="number"
                      min="0"
                      step="0.01"
                      required
                      value={line.unit_price}
                      onChange={(e) => updateLine(line._key, { unit_price: e.target.value === "" ? "" : Number(e.target.value) })}
                      className={inputClass}
                    />
                    {errors[`${errPfx}.unit_price`] && (
                      <span className="text-xs text-destructive">{errors[`${errPfx}.unit_price`]}</span>
                    )}
                  </td>
                  <td className={tdClass}>
                    <Input
                      type="number"
                      min="0"
                      max="100"
                      step="0.1"
                      value={line.discount_pct}
                      onChange={(e) => updateLine(line._key, { discount_pct: e.target.value === "" ? "" : Number(e.target.value) })}
                      className={inputClass}
                    />
                  </td>
                  <td className={`${tdClass} text-right tabular-nums`}>
                    {COP(saleLineSubtotal(line))}
                  </td>
                  <td className={tdClass}>
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      className="h-8 w-8 p-0 text-muted-foreground hover:text-destructive"
                      onClick={() => removeLine(line._key)}
                      disabled={items.length === 1}
                      aria-label="Eliminar línea"
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      <div className="flex items-center justify-between pt-1">
        <Button type="button" variant="outline" size="sm" onClick={addLine}>
          <Plus className="h-3.5 w-3.5" /> Agregar línea
        </Button>
        <span className="text-sm font-medium">Subtotal: {COP(subtotal)}</span>
      </div>
    </div>
  );
}
