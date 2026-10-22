<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\FinancialTransactionResource;
use App\Models\FinancialTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialTransactionController extends BaseCrudController
{
    protected string $model      = FinancialTransaction::class;
    protected string $resource   = FinancialTransactionResource::class;
    protected array  $with       = ['cashAccount', 'user'];
    protected array  $searchable = ['description'];
    protected array  $filterable = ['type', 'reference_type', 'cash_account_id'];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $q = FinancialTransaction::where('company_id', $cid);
        return response()->json([
            'total'      => $q->count(),
            'income'     => $q->clone()->whereIn('type', ['income', 'transfer_in'])->count(),
            'expense'    => $q->clone()->whereIn('type', ['expense', 'transfer_out'])->count(),
            'this_month' => $q->clone()->whereMonth('date', now()->month)->whereYear('date', now()->year)->count(),
        ]);
    }
}
