<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CashAccountResource;
use App\Models\CashAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashAccountController extends BaseCrudController
{
    protected string $model      = CashAccount::class;
    protected string $resource   = CashAccountResource::class;
    protected array  $searchable = ['name'];
    protected array  $filterable = ['type', 'status'];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $q   = CashAccount::where('company_id', $cid);
        return response()->json([
            'total'         => $q->clone()->count(),
            'active'        => $q->clone()->where('status', 'active')->count(),
            'total_balance' => round((float) $q->clone()->where('status', 'active')->sum('balance'), 2),
        ]);
    }
}
