<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AccountPayableResource;
use App\Models\AccountPayable;

class AccountPayableController extends BaseCrudController
{
    protected string $model    = AccountPayable::class;
    protected string $resource = AccountPayableResource::class;
    protected array $with       = ['supplier:id,name', 'purchaseInvoice:id,number,total'];
    protected array $filterable = ['status' => 'status', 'supplier_id' => 'supplier_id'];
}
