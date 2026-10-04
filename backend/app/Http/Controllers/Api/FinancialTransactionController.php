<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\FinancialTransactionResource;
use App\Models\FinancialTransaction;

class FinancialTransactionController extends BaseCrudController
{
    protected string $model      = FinancialTransaction::class;
    protected string $resource   = FinancialTransactionResource::class;
    protected array  $with       = ['cashAccount', 'user'];
    protected array  $searchable = ['description'];
    protected array  $filterable = ['type', 'reference_type', 'cash_account_id'];
}
