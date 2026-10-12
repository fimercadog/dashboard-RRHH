<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Http\Request;

trait ResolvesCompany
{
    protected function companyId(Request $request): int
    {
        $companyId = $request->user()?->company_id;

        abort_if($companyId === null, 403, 'No hay empresa asociada al usuario.');

        return (int) $companyId;
    }
}
