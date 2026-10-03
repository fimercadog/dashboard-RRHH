<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'sku'         => $this->sku,
            'name'        => $this->name,
            'description' => $this->description,
            'type'        => $this->type,
            'cost_price'  => $this->cost_price,
            'sale_price'  => $this->sale_price,
            'min_stock'   => $this->min_stock,
            'status'      => $this->status,
            'category'    => $this->whenLoaded('category', fn () => ['id' => $this->category->id, 'name' => $this->category->name]),
            'brand'       => $this->whenLoaded('brand', fn () => ['id' => $this->brand->id, 'name' => $this->brand->name]),
            'unit'        => $this->whenLoaded('unit', fn () => ['id' => $this->unit->id, 'name' => $this->unit->name, 'abbreviation' => $this->unit->abbreviation]),
        ];
    }
}
