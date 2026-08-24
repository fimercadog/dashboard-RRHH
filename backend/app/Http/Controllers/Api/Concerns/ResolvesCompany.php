<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Company;
use Illuminate\Http\Request;

trait ResolvesCompany
{
    protected function companyId(Request $request): int
    {
        return (int) ($request->user()?->company_id ?? Company::query()->value('id') ?? 1);
    }
}
