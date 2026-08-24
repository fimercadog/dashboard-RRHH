<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TableQueryService
{
    public function apply(Request $request, Builder $query, array $searchable = [], array $filterable = []): Builder
    {
        if ($search = $request->string('search')->trim()->toString()) {
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
