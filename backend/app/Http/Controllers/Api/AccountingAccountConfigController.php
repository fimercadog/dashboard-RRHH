<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AccountingAccountConfigResource;
use App\Models\AccountingAccountConfig;

class AccountingAccountConfigController extends BaseCrudController
{
    protected string $model    = AccountingAccountConfig::class;
    protected string $resource = AccountingAccountConfigResource::class;
    protected array  $with     = ['account'];
    protected array  $filterable = ['config_key'];
}
