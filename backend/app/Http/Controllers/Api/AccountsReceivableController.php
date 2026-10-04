<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AccountsReceivableResource;
use App\Models\AccountsReceivable;

class AccountsReceivableController extends BaseCrudController
{
    protected string $model    = AccountsReceivable::class;
    protected string $resource = AccountsReceivableResource::class;
    protected array $with       = ['client:id,first_name,last_name', 'saleInvoice:id,number,total'];
    protected array $filterable = ['status' => 'status', 'client_id' => 'client_id'];
}
