<?php

namespace App\Services\Import\Handlers;

use App\Contracts\ImportableEntity;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Support\Facades\Validator;

class ProductImportHandler implements ImportableEntity
{
    public function modelClass(): string
    {
        return Product::class;
    }

    public function requiredColumns(): array
    {
        return ['name'];
    }

    public function optionalColumns(): array
    {
        return ['sku', 'type', 'cost_price', 'sale_price', 'min_stock', 'status', 'category', 'brand', 'unit'];
    }

    public function duplicateKeys(): array
    {
        return [];
    }

    public function validateRow(array $row, int $rowNumber, int $companyId): array
    {
        $rules = [
            'name'       => ['required', 'string', 'max:150'],
            'sku'        => ['nullable', 'string', 'max:60'],
            'type'       => ['nullable', 'in:storable,service,consumable'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'min_stock'  => ['nullable', 'numeric', 'min:0'],
            'status'     => ['nullable', 'in:active,inactive'],
            'category'   => ['nullable', 'string', 'max:100'],
            'brand'      => ['nullable', 'string', 'max:100'],
            'unit'       => ['nullable', 'string', 'max:60'],
        ];

        $validator = Validator::make($row, $rules);

        if ($validator->fails()) {
            $messages = implode('; ', $validator->errors()->all());
            throw new \RuntimeException($messages);
        }

        return $validator->validated();
    }

    public function mapRow(array $validated, int $companyId): array
    {
        $row = $validated;
        $row['company_id'] = $companyId;
        $row['type']       = $row['type'] ?? 'storable';
        $row['status']     = $row['status'] ?? 'active';

        foreach (['cost_price', 'sale_price', 'min_stock'] as $priceField) {
            if (isset($row[$priceField])) {
                $row[$priceField] = round((float) $row[$priceField], 4);
            }
        }

        // Resolve category name → id
        if (!empty($row['category'])) {
            $name = $row['category'];
            unset($row['category']);

            $results = Category::where('company_id', $companyId)->where('name', $name)->get();

            if ($results->count() === 0) {
                throw new \RuntimeException("Categoría '{$name}' no encontrada para esta empresa.");
            }
            if ($results->count() > 1) {
                throw new \RuntimeException("Categoría '{$name}' es ambigua (existe más de un registro).");
            }

            $row['category_id'] = $results->first()->id;
        } else {
            unset($row['category']);
        }

        // Resolve brand name → id
        if (!empty($row['brand'])) {
            $name = $row['brand'];
            unset($row['brand']);

            $results = Brand::where('company_id', $companyId)->where('name', $name)->get();

            if ($results->count() === 0) {
                throw new \RuntimeException("Marca '{$name}' no encontrada para esta empresa.");
            }
            if ($results->count() > 1) {
                throw new \RuntimeException("Marca '{$name}' es ambigua (existe más de un registro).");
            }

            $row['brand_id'] = $results->first()->id;
        } else {
            unset($row['brand']);
        }

        // Resolve unit name → id
        if (!empty($row['unit'])) {
            $name = $row['unit'];
            unset($row['unit']);

            $results = Unit::where('company_id', $companyId)->where('name', $name)->get();

            if ($results->count() === 0) {
                throw new \RuntimeException("Unidad '{$name}' no encontrada para esta empresa.");
            }
            if ($results->count() > 1) {
                throw new \RuntimeException("Unidad '{$name}' es ambigua (existe más de un registro).");
            }

            $row['unit_id'] = $results->first()->id;
        } else {
            unset($row['unit']);
        }

        $now = now()->toDateTimeString();
        $row['created_at'] = $now;
        $row['updated_at'] = $now;

        return $row;
    }
}
