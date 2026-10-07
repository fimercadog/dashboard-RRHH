<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TableQueryService
{
    const DEFAULT_PER_PAGE = 10;
    const MAX_PER_PAGE = 100;

    public function apply(Request $request, Builder $query, array $searchable = [], array $filterable = []): Builder
    {
        if ($search = $request->string('search')->trim()->toString()) {
            // ponytail: LIKE con leading wildcard, migrar a FULLTEXT cuando search sea lento en empresas >500k registros/tabla
            $query->where(function (Builder $builder) use ($searchable, $search): void {
                foreach ($searchable as $field) {
                    $builder->orWhere($field, 'like', "%{$search}%");
                }
            });
        }

        foreach ($filterable as $param => $field) {
            if ($request->filled($param)) {
                $query->where($field, $request->input($param));
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate($request->input('date_field', 'created_at'), '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate($request->input('date_field', 'created_at'), '<=', $request->date('date_to'));
        }

        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction);
    }
}
