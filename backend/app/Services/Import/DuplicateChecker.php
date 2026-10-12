<?php

namespace App\Services\Import;

use App\Contracts\ImportableEntity;

class DuplicateChecker
{
    /**
     * Returns 'none' | 'active' | 'soft_deleted'
     */
    public function check(ImportableEntity $handler, array $row, int $companyId): string
    {
        if ($handler->duplicateKeys() === []) {
            return 'none';
        }

        $modelClass = $handler->modelClass();

        $usesSoftDeletes = in_array(
            \Illuminate\Database\Eloquent\SoftDeletes::class,
            class_uses_recursive($modelClass)
        );

        $q = $usesSoftDeletes
            ? $modelClass::withTrashed()->where('company_id', $companyId)
            : $modelClass::query()->where('company_id', $companyId);

        $keys = $handler->duplicateKeys();
        $q->where(function ($query) use ($keys, $row) {
            foreach ($keys as $key) {
                $query->orWhere($key, $row[$key] ?? null);
            }
        });

        $found = $q->first();

        if ($found === null) {
            return 'none';
        }

        if ($found->deleted_at !== null) {
            return 'soft_deleted';
        }

        return 'active';
    }
}
