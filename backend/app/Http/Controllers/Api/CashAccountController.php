<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CashAccountResource;
use App\Models\CashAccount;

class CashAccountController extends BaseCrudController
{
    protected string $model      = CashAccount::class;
    protected string $resource   = CashAccountResource::class;
    protected array  $searchable = ['name'];
    protected array  $filterable = ['type', 'status'];
}
