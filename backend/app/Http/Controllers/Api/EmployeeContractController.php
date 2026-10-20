<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\EmployeeContractResource;
use App\Models\EmployeeContract;

class EmployeeContractController extends BaseCrudController
{
    protected string $model    = EmployeeContract::class;
    protected string $resource = EmployeeContractResource::class;
    protected array  $with     = ['employee'];
    protected array  $searchable  = ['contract_number'];
    protected array  $filterable  = ['status' => 'status', 'employee_id' => 'employee_id'];
    protected array  $immutableOnUpdate = ['company_id', 'employee_id', 'created_by'];
}
