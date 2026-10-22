<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AccountPayableResource;
use App\Models\AccountPayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountPayableController extends BaseCrudController
{
    protected string $model    = AccountPayable::class;
    protected string $resource = AccountPayableResource::class;
    protected array $with       = ['supplier:id,name', 'purchaseInvoice:id,number,total'];
    protected array $filterable = ['status' => 'status', 'supplier_id' => 'supplier_id'];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $q = AccountPayable::where('company_id', $cid);
        return response()->json([
            'total'   => $q->count(),
            'pending' => $q->clone()->whereIn('status', ['pending', 'partial'])->count(),
            'overdue' => $q->clone()->where('due_date', '<', now()->toDateString())
                           ->whereNotIn('status', ['paid'])->count(),
        ]);
    }
}
