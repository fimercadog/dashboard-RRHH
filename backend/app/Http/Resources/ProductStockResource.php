<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductStockResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'        => $this->id,
            'quantity'  => $this->quantity,
            'avg_cost'  => $this->avg_cost,
            'product'   => $this->whenLoaded('product', fn () => [
                'id'        => $this->product->id,
                'sku'       => $this->product->sku,
                'name'      => $this->product->name,
                'min_stock' => $this->product->min_stock,
                'type'      => $this->product->type,
            ]),
            'warehouse' => $this->whenLoaded('warehouse', fn () => [
                'id'   => $this->warehouse->id,
                'name' => $this->warehouse->name,
            ]),
        ];
    }
}
