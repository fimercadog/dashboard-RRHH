<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AccountsReceivableResource;
use App\Models\AccountsReceivable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountsReceivableController extends BaseCrudController
{
    protected string $model    = AccountsReceivable::class;
    protected string $resource = AccountsReceivableResource::class;
    protected array $with       = ['client:id,first_name,last_name', 'saleInvoice:id,number,total'];
    protected array $filterable = ['status' => 'status', 'client_id' => 'client_id'];

    public function stats(Request $request): JsonResponse
    {
        $cid = $this->companyId($request);
        $q = AccountsReceivable::where('company_id', $cid);
        return response()->json([
            'total'   => $q->count(),
            'pending' => $q->clone()->whereIn('status', ['pending', 'partial'])->count(),
            'overdue' => $q->clone()->where('due_date', '<', now()->toDateString())
                           ->whereNotIn('status', ['paid'])->count(),
        ]);
    }
}
